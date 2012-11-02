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
				id		bigint not null,
				name		text,
				php_script	text,
				php_class	text	
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
				id		bigint not null,
				id_type		bigint not null,
				url		text,
				cached		boolean default true
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
			        id		bigint not null,
				id_feed		bigint not null,
				date		timestamp,
				title		text,	
				image		text,
				detail		text
			);		
			create sequence seq_feed_contents;
			alter table feed_contents alter column id set default nextval('seq_feed_contents');
			create unique index pk_feed_contents__id on feed_contents(id);
			create unique index pk_feed_contents on feed_contents(id_feed, date);
			alter table feed_contents add primary key using index pk_feed_contents;
			create index fk_feed_contents__id_feed on feed_contents(id_feed);
			alter table feed_contents add foreign key (id_feed) references feeds(id);

			grant select, insert on feed_contents to signage;

			insert into feed_contents (id_feed,date, title, image,detail) values (1,'2012-10-29','test1','url1','detail1');
			insert into feed_contents (id_feed,date, title, image,detail) values (1,'2012-10-30','test2','url2','detail2');

		else
			raise notice 'Database already setup, skipping';
		end if;
	end;
$$;


