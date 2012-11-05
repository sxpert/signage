-------------------------------------------------------------------------------
-- Signage application database
--
--
--

-- this script should be called from template0 from a db super user account

-- avoid stupid messages
--\set VERBOSITY terse
\set VERBOSITY normal

do $$
	declare
		apod_id bigint;
	begin
		perform tablename from pg_tables where tablename = 'version';
		if not found then
			raise notice 'No version found, creating database';

			-- create a user
			perform  usename from pg_user where usename='signage';
			if not found then
				create user signage with encrypted password 'eiph8Ahg';
			end if;

			-- version table.
			raise notice 'Creating version table';
			create table version (
				version		bigint
			);	
			insert into version (version) values (1);

			-- types of feeds.
			raise notice 'Creating feed types';
			create table feed_types (
				id		      bigint not null,
				name		    text,
				php_script	text,
				php_class	  text	
			);
			create sequence seq_feed_types;
			alter table feed_types alter column id set default nextval('seq_feed_types');
			create unique index pk_feed_types__id on feed_types(id);
			alter table feed_types add primary key using index pk_feed_types__id;
			create unique index ix_feed_types__name on feed_types(name);

			grant select on feed_types to signage;

			insert into feed_types (name, php_script, php_class) values 
				('rss',  '/lib/feeds/rss.php',  'FeedRSS'), 
				('apod', '/lib/feeds/apod.php', 'FeedAPOD');

			-- feeds
			-- cached : defines if the feed contents is to be saved 
			raise notice 'Creating feeds';
			create table feeds (
				id		  bigint not null,
				id_type	bigint not null,
				url		  text,
				cached	boolean default true
			);
			create sequence seq_feeds;
			alter table feeds alter column id set default nextval('seq_feeds');
			create unique index pk_feeds__id on feeds(id);
			alter table feeds add primary key using index pk_feeds__id;
			alter table feeds add foreign key (id_type) references feed_types(id);
			create unique index ix_feeds__url on feeds(url);

			select id into apod_id from feed_types where name='apod';
			insert into feeds (id_type, url) values
				(apod_id, 'http://apod.nasa.gov/apod/archivepix.html');
			
			grant select on feeds to signage;

			create table feed_contents (
			  id		  bigint not null,
				id_feed	bigint not null,
				date		timestamp,
				title		text,	
				image		text,
				detail	text
			);		
			create sequence seq_feed_contents;
			grant usage on seq_feed_contents to signage;
			alter table feed_contents alter column id set default nextval('seq_feed_contents');
			create unique index pk_feed_contents__id on feed_contents(id);
			create unique index pk_feed_contents on feed_contents(id_feed, date);
			alter table feed_contents add primary key using index pk_feed_contents;
			create index fk_feed_contents__id_feed on feed_contents(id_feed);
			alter table feed_contents add foreign key (id_feed) references feeds(id);

			grant select, insert on feed_contents to signage;

		else
			raise notice 'Database already setup, skipping';
		end if;
	end;
$$;

--
-- version 2 :
-- add an update_version function
--
do $$
  declare
    t_curr_ver bigint;
  begin
    select version into t_curr_ver from version limit 1;
    raise notice 'version %', t_curr_ver;
    if t_curr_ver = 1 then
      create or replace function update_version (l_expected bigint, l_new bigint) returns boolean as $q$
        declare
          t_curr_ver bigint;
        begin
          select version into t_curr_ver from version limit 1;
          if t_curr_ver = l_expected then
            update version set version=l_new where version=t_curr_ver;
            raise notice 'found the expected version %, updating to %', l_expected, l_new;
            return true;
          end if;
          return false;
        end;
      $q$ language plpgsql;
      perform update_version (1, 2);
    end if;
  end;
$$;

--
-- add initial screen management
-- 
do $$
  begin
    if update_version(2,3) then
      create table screens (
        id        bigint not null,
        screen_ip inet not null,
        enabled   boolean default false
      );
      create sequence seq_screens;
      grant usage on seq_screens to signage;
      create unique index pk_screens on screens(id);
      alter table screens add primary key using index pk_screens;
      create unique index un_screens__screen_ip on screens(screen_ip);
      grant select, insert, update on screens to signage;

      create table screen_feeds (
        id_screen bigint not null,
        id_feed   bigint not null
      );
      create index fk_screen_feeds__id_screen on screen_feeds(id_screen);
      alter table screen_feeds add foreign key (id_screen) references screens(id);
      create index fk_screen_feeds__id_feed on screen_feeds(id_feed);
      alter table screen_feeds add foreign key (id_feed) references feeds(id);
      create unique index un_screen_feeds on screen_feeds(id_screen, id_feed);
      grant select, insert, update on screen_feeds to signage;
      
    end if;
  end;
$$;

--
-- add some functionnality to the screens and screen_feeds table
--
do $$
  begin
    if update_version(3,4) then
      -- screen name
      alter table screens add column name text;
      -- last feed displayed
      alter table screens add column current_feed bigint;
      create index fk_screens__current_feed on screens (current_feed);
      alter table screens add constraint fk_screens__current_feed foreign key (current_feed) references feeds(id);
      -- screen was adopted in UI
      alter table screens add column adopted boolean default false;
      -- ordering feeds for each screen
      alter table screen_feeds add column feed_order bigint;
      -- add some uniaue index to prevent having feeds with the same order
      create unique index un_screen_feeds__feed_order on screen_feeds (id_screen, feed_order);
      -- feed is active for screen
      alter table screen_feeds add column active boolean default false;
      -- last item of feed displayed
      alter table screen_feeds add column current_item bigint;
      create index fk_screen_feeds__current_item on screen_feeds (current_item);
      alter table screen_feeds add constraint fk_screen_feeds__current_item foreign key (current_item) references feed_contents(id);
      -- get rid of the cahed attribute, it won't be used...
      alter table feeds drop column if exists cached;
      -- find the id of a screen by ip address 
      create or replace function get_screen_id (l_screen_ip inet) returns bigint as $q$
        declare
          t_screen_id bigint;
        begin
          -- find if we have this screen in the database
          select id into t_screen_id from screens where screen_ip = l_screen_ip;
          if not found then
            -- add the screen as new
            insert into screens (screen_ip) values (l_screen_ip) returning id into t_screen_id;  
          end if;
          return t_screen_id;
        end;
      $q$ language plpgsql;
      -- find the next feed for a given screen 
      create or replace function get_next_feed_id (l_screen_id bigint) returns bigint as $q$
        declare
          t_current_feed bigint;
          t_next_feed    bigint;
        begin
          select current_feed into t_current_feed from screens where id = l_screen_id;
          raise notice 'current_feed %',t_current_feed;
          if not found then
            -- unable to find the line for that screen id
            raise notice 'not found, returning null';
            return null;
          end if;
          if t_current_feed is null then
            raise notice 't_current_feed is null';
            -- find the lower number feed for this screen
            select min(id_feed) into t_next_feed from screen_feeds where id_screen = l_screen_id;
            raise notice 't_next_feed %',t_next_feed;
            -- no feed for this screen, return null !
            if not found then
              raise notice 'not found, return null';
              return null;
            end if;
          else
            select id_feed into t_next_feed from screen_feeds where id_screen = l_screen_id and id_feed > t_current_feed order by id_feed limit 1;
            raise notice 't_next_feed %',t_next_feed;
            if not found then
              -- get first feed
              select min(id_feed) into t_next_feed from screen_feeds where id_screen = l_screen_id;
              raise notice 't_next_feed %',t_next_feed;
            end if;
          end if;
          -- update the record
          update screens set current_feed = t_next_feed where id = l_screen_id;
          return t_next_feed;
        end;
      $q$ language plpgsql;
    end if;
  end;
$$;

do $$
  begin
    if update_version(4,5) then
			alter table screens alter column id set default nextval('seq_screens');
    end if;
  end;
$$;

do $$
  begin
      -- function to get the next feed_content
      create or replace function get_next_feed_content (l_screen_id bigint, l_feed_id bigint) returns record as $q$
        declare
          t_curr_content_id bigint;
          t_next_content_id bigint;
          t_next_content    record;
        begin
          t_next_content_id := null; 
          select current_item into t_curr_content_id from screen_feeds where id_screen = l_screen_id and id_feed = l_feed_id;
          if not found then
            return null;
          end if;
          if t_curr_content_id is null then
            -- find the first item from the feed
            select min(id) into t_next_content_id from feed_contents where id_feed = l_feed_id;
            if not found then
              return null;
            end if;
          end if;
          select  id into t_next_content_id from feed_contents where id_feed = l_feed_id and id > t_curr_content_id order by id limit 1;
          if not found then
            select min(id) into t_next_content_id from feed_contents where id_feed = l_feed_id;
            if not found then
              return null;
            end if;
          end if;
          -- update the feed_content_id
          update screen_feeds set current_item = t_next_content_id where id_screen = l_screen_id and id_feed = l_feed_id;
          select * into t_next_content from feed_contents where id = t_next_content_id;
          return t_next_content;
        end;
      $q$ language plpgsql;
  end;
$$;

