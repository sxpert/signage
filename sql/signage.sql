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
      
      --------------------------------------------------------------------------
      --
      -- create a user to be used by the web platform
      --
      perform  usename from pg_user where usename='signage';
      if not found then
        create user signage with encrypted password 'eiph8Ahg';
      end if;

      --------------------------------------------------------------------------
      --      
      -- managing database versions
      --

      -- first, create a table
      raise notice 'Creating version table';
      create table version (
      version		bigint
      );	
      insert into version (version) values (1);

      -- then add a function to check and update version
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

      --------------------------------------------------------------------------
      --
      -- feeds
      -- 

      --
      -- types of feeds.
      --
      raise notice 'Creating feed types';
      create table feed_types (
        id	   bigint not null,
        name	   text,
        php_script text,
        php_class  text	
      );

      create sequence seq_feed_types;
      alter table feed_types alter column id set default nextval('seq_feed_types');

      create unique index pk_feed_types__id on feed_types(id);
      alter table feed_types add primary key using index pk_feed_types__id;
      create unique index ix_feed_types__name on feed_types(name);

      grant select on feed_types to signage;
      
      -- insert basic feed types
      insert into feed_types (name, php_script, php_class) values 
				('rss',  '/lib/feeds/rss.php',  'FeedRSS'), 
				('apod', '/lib/feeds/apod.php', 'FeedAPOD'),
        ('manuel', '/lib/feeds/manual.php', 'FeedManual');
	
      --	
      -- feeds
      --
      -- cached : defines if the feed contents is to be saved 
      raise notice 'Creating feeds';
      create table feeds (
				id			bigint not null,
				id_type	bigint not null,
      	name 		text,
				url			text,
       	system 	boolean default false
     );

      create sequence seq_feeds;
      alter table feeds alter column id set default nextval('seq_feeds');

      create unique index pk_feeds__id on feeds(id);
      alter table feeds add primary key using index pk_feeds__id;
      alter table feeds add foreign key (id_type) references feed_types(id);
      create unique index ix_feeds__url on feeds(url);

      grant select on feeds to signage;

      -- insert the APOD feed
      select id into apod_id from feed_types where name='apod';
      insert into feeds (id_type, name, url, system) values
      	(apod_id, 'Astronomy Picture Of the Day', 'http://apod.nasa.gov/apod/archivepix.html', true);
			
      --
      -- contents of feeds (cached data)
      --
      create table feed_contents (
        id   	   	bigint not null,
				id_feed		bigint 	not null,
				date			timestamp,
				title			text,	
				image			text,
				detail		text
      );		

      create sequence seq_feed_contents;
      grant usage on seq_feed_contents to signage;
      alter table feed_contents alter column id set default nextval('seq_feed_contents');

      create unique index pk_feed_contents__id on feed_contents(id);
      create unique index pk_feed_contents on feed_contents(id_feed, date);
      alter table feed_contents add primary key using index pk_feed_contents;
      create index fk_feed_contents__id_feed on feed_contents(id_feed);
      alter table feed_contents add foreign key (id_feed) references feeds(id);

      grant select, insert, update on feed_contents to signage;

      --------------------------------------------------------------------------
      --
      -- Screens
      --

      --
      -- screens table
      -- 
      create table screens (
        id           	bigint not null,
        screen_ip    	inet not null,
				name 	     		text,
        enabled      	boolean default false,
				current_feed 	bigint,
      	adopted      	boolean default false
      );
      
      create sequence seq_screens;
      alter table screens alter column id set default nextval('seq_screens');
      grant usage on seq_screens to signage;

      create unique index pk_screens on screens(id);
      alter table screens add primary key using index pk_screens;
      create unique index un_screens__screen_ip on screens(screen_ip);
      create index fk_screens__current_feed on screens (current_feed);
      alter table screens add constraint fk_screens__current_feed foreign key (current_feed) references feeds(id);

      grant select, insert, update on screens to signage;

      --
      -- feeds for each screen
      --
      create table screen_feeds (
        id_screen    bigint not null,
        id_feed      bigint not null,
      	feed_order   bigint,
      	active 	     boolean default false,
      	target 	     text,
      	current_item bigint
      );
      
      create index fk_screen_feeds__id_screen on screen_feeds(id_screen);
      alter table screen_feeds add foreign key (id_screen) references screens(id);
      create index fk_screen_feeds__id_feed on screen_feeds(id_feed);
      alter table screen_feeds add foreign key (id_feed) references feeds(id);
      create unique index un_screen_feeds on screen_feeds(id_screen, id_feed);
      create unique index un_screen_feeds__feed_order on screen_feeds (id_screen, feed_order);
      create index fk_screen_feeds__current_item on screen_feeds (current_item);
      alter table screen_feeds add constraint fk_screen_feeds__current_item foreign key (current_item) references feed_contents(id);

      grant select, insert, update on screen_feeds to signage;
      

    else
      raise notice 'Database already setup, skipping';
    end if;
  end;
$$;

do $$
  declare
    apod_id	bigint;
  begin
    if update_version(1,3) then
    	--
			-- champ dateonly boolean, afficher seulement la date pour ce feed
			--
			alter table feeds add column dateonly boolean default false;
      select id into apod_id from feed_types where name='apod';
			update feeds set dateonly = true where id=apod_id;
			--
			-- champ active sur chaque item
			--
			alter table feed_contents add column active boolean default false;
			update feed_contents set active=true;
    end if; 
  end;
$$;

do $$
	begin
		if update_version(3,4) then
			-- 
			-- vue pour les types de flux
			--
			create or replace view feed_type_list as select id as key, name as value from feed_types order by name;
		end if;
	end;
$$;
do $$
	begin
		if update_version(4,5) then
			-- 
			-- vue pour les types de flux
			--
			grant select on feed_type_list to signage;
		end if;
	end;
$$;
do $$
	begin
		if update_version(5,6) then
			-- 
			-- ajout d'un champ systeme pour les types de flux (empeche de créer des flux de ce type par l'interface)
			--
			alter table feed_types add column system boolean default false;
			--
			-- rends le flux apod 'systeme'
			--
			update feed_types set system=true where name='apod';
			-- 
			-- vue pour le select de la liste des types
			-- 
			create or replace view feed_type_list as select id as key, name as value from feeds where system=false order by name;
		end if;
	end;
$$;
do $$
	begin
		if update_version(6,7) then
			-- 
			-- l'utilisateur signage doit pouvoir modifier les flux
			--
			grant insert, update on feeds to signage;
		end if;
	end;
$$;
do $$
	begin
		if update_version(7,8) then
			-- 
			-- l'utilisateur signage doit pouvoir modifier les flux
			--
			grant usage on seq_feeds to signage;
		end if;
	end;
$$;
do $$
	begin
		if update_version(8,9) then
			-- 
			-- index unique sur les noms des flux
			--
			alter table feeds alter column name set not null;
      create unique index un_feeds__name on feeds(name);
		end if;
	end;
$$;
do $$
	begin
		if update_version(9,11) then
			-- 
			-- index unique sur les noms des flux
			--
			drop index ix_feeds__url;
      create unique index un_feeds__url_name on feeds(url,name);
		end if;
	end;
$$;
do $$
	begin
		if update_version(11,12) then
			-- 
			-- vue sur les flux pour le select
			--
			create or replace view feed_list as select id as key, name as value from feeds order by name;
			grant select on feed_list to signage;
		end if;
	end;
$$;
do $$
	begin
		if update_version(12,13) then
			alter table screens drop constraint fk_screens__current_feed;
		end if;
	end;
$$;
--
-- ajoute un champ last_update aux flux
--
do $$
	begin
		if update_version(13,14) then
			alter table feeds add column last_update timestamp;
		end if;
	end;
$$;
--
-- ajout d'un champ "ignored" défault a "false" pour les écrans
--
do $$
	begin
		if update_version(14,15) then
			alter table screens add column ignored boolean default false;
		end if;
	end;
$$;
--
-- ajout d'une table des utilisateurs autorisés a utiliser l'application
-- on a juste les logins pour l'instant, 
-- ils doivent correspondre aux login dans le ldap
--
do $$
	begin
		if update_version(15,16) then
			create table users (
				uid	text primary key
			);
			grant select on users to signage;
			--
			-- trois utilisateurs connus
			--
			insert into users (uid) values
				('jacquotr'),
				('rousself'),
				('lmichaud');
		end if;
	end;
$$;

--
-- ces fonctions sont 'in flux'
--
do $$
  begin

      --------------------------------------------------------------------------
      --
      -- récupère l'id numérique de l'écran dont l'ip est passée en paramètre
      --
      -- si l'écran n'est pas connu, ajoute un enregistrement dans la table
      -- des écrans
      --
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

      --------------------------------------------------------------------------
      --
      -- find the next feed for a given screen 
      -- TODO: handle more of the available options
      --
      create or replace function get_next_feed_id (l_screen_id bigint) returns bigint as $q$
        declare
          t_curr_index bigint;
					t_next_index bigint;
          t_next_feed  bigint;
        begin
          select current_feed into t_curr_index from screens 
						where id = l_screen_id and adopted = true and enabled = true for update;
          raise notice 'current_feed %',t_curr_index;
          if not found then
            -- unable to find the line for that screen id
            raise notice 'not found, returning null';
            return null;
          end if;
          if t_curr_index is null then
            raise notice 't_curr_index is null';
            -- find the lower number feed for this screen
            select min(feed_order) into t_next_index from screen_feeds 
							where id_screen = l_screen_id and active = true;
            raise notice 't_next_feed %',t_next_index;
            -- no feed for this screen, return null !
            if not found then
              raise notice 'not found, return null';
              return null;
            end if;
          else
            select feed_order into t_next_index from screen_feeds 
							where id_screen = l_screen_id and feed_order > t_curr_index and active = true
							order by feed_order limit 1;
            raise notice 't_next_index %',t_next_index;
            if not found then
              -- get first feed
              select min(feed_order) into t_next_index from screen_feeds 
								where id_screen = l_screen_id and active = true;
              raise notice 't_next_index %',t_next_index;
            end if;
          end if;
          -- update the record
          update screens set current_feed = t_next_index where id = l_screen_id;
					-- get the corresponding feed_id
					select id_feed into t_next_feed from screen_feeds 
						where id_screen = l_screen_id and feed_order = t_next_index;
          return t_next_feed;
        end;
      $q$ language plpgsql;


      --------------------------------------------------------------------------
      --
      -- function to get the next feed_content
      -- TODO: handle more of the available options
      --

			-- 
			-- function to get the first item in the feed
			-- 
			create or replace function feed_get_first_item_id (l_feed_id bigint) returns bigint as $q$
				declare
					t_next bigint;
				begin
          select id into t_next from feed_contents 
						where date = (select min(date) from feed_contents where id_feed=l_feed_id and active=true) 
									and id_feed = l_feed_id ;
            if not found then
              return null;
            end if;
						return t_next;
				end;
			$q$ language plpgsql;

			--
			-- function to get the last item in the feed
			--
			create or replace function feed_get_next_item_id (l_feed_id bigint, l_item_id bigint) returns bigint as $q$
				declare
					t_next bigint;
				begin
         	select id into t_next from feed_contents 
						where id_feed = l_feed_id and date > (select date from feed_contents where id = l_item_id)
							    and active = true order by date limit 1;
         	if not found then
						t_next := feed_get_first_item_id (l_feed_id);
         	end if;
					return t_next;
				end;
			$q$ language plpgsql;

			--
			-- function to get the contents of a feed item
			--
			create or replace function feed_get_item (l_screen_id bigint, l_feed_id bigint, l_item_id bigint) returns record as $q$
				declare
					t_target  text;
					t_content record;
				begin
					select target into t_target from screen_feeds where id_screen=l_screen_id and id_feed=l_feed_id;
					select *, t_target as target into t_content from feed_contents where id=l_item_id;
					return t_content;
				end;
			$q$ language plpgsql;

			--
			-- function to get the contents of the next feed item
			--
			create or replace function get_next_feed_content (l_screen_id bigint, l_feed_id bigint) returns record as $q$
        declare
          t_current bigint;
          t_next		bigint;
					t_content	record;
        begin
          t_next := null;
          -- get the current item and target
          select current_item into t_current from screen_feeds where id_screen = l_screen_id and id_feed = l_feed_id for update;
          if not found then
            return null;
          end if;
					-- there is no item yet
          if t_current is null then
            -- find the first item from the feed by date
						t_next := feed_get_first_item_id (l_feed_id);
          else
						t_next := feed_get_next_item_id (l_feed_id, t_current);
					end if;
          -- update the feed_content_id
					if t_next is not null then
	          update screen_feeds set current_item = t_next where id_screen = l_screen_id and id_feed = l_feed_id;
    	      t_content := feed_get_item(l_screen_id, l_feed_id, t_next);
						return t_content;
					end if;
					return null;
        end;
      $q$ language plpgsql;

			-------------------------------------------------------------------------
			--
			-- ajoute un flux a un écran
			-- 
			--
			create or replace function screen_append_feed (l_screen bigint,l_feed bigint,l_active boolean,l_target text) returns boolean as $q$
				declare
					t_order	bigint;
				begin
					-- lock table
					perform * from screen_feeds for update;
					-- find next available position
					select max(feed_order) into t_order from screen_feeds where id_screen = l_screen;
					if t_order is null then
						t_order := 0;
					end if;
					begin
						insert into screen_feeds (id_screen, id_feed, feed_order, active, target)
							values (l_screen, l_feed, (t_order+1), l_active, l_target);
					exception when unique_violation then
						return false;
					end;
					return true;
				end;
			$q$ language plpgsql;

			-------------------------------------------------------------------------
			-- 
			-- Mets a jour les flux dans l'écran
			-- 
			-- 

			create or replace function screen_active_feeds (l_screen bigint, l_feeds bigint array) returns boolean as $q$
				declare
					t_feed record;
					t_active boolean;
				begin
					raise notice '%', l_feeds;
					-- TODO: check if array contents are within feed ids
					for t_feed in select * from screen_feeds where id_screen = l_screen for update loop
						raise notice '%', t_feed;
						t_active := array[ t_feed.id_feed ] <@ l_feeds;
						update screen_feeds set active=t_active where id_screen = l_screen and id_feed = t_feed.id_feed;
					end loop;
					return true;
				end;
			$q$ language plpgsql;
  end;
$$;

