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
			create user 'signage' with encryped password 'eiph8Ahg';

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
				name		text	
			);
			create sequence seq_feed_types;
			alter table feed_types alter column id set default nextval('seq_feed_types');
			create unique index pk_feed_types__id on feed_types(id);
			alter table feed_types add primary key using index pk_feed_types__id;
			create unique index ix_feed_types__name on feed_types(name);

			insert into feed_types (name) values 
				('rss'), ('apod');

			-- feeds themselves
			raise notice 'Creating feeds';
			create table feeds (
				id		bigint not null,
				id_type		bigint not null,
				url		text
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

		else
			raise notice 'Database already setup, skipping';
		end if;
	end;
$$;


