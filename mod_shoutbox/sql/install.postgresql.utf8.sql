CREATE TABLE "#__shoutbox" ( 
  "id" serial NOT NULL, 
  "name" character varying(25) DEFAULT '' NOT NULL, 
  "when" timestamp without time zone, 
  "ip" character varying(15) DEFAULT '' NOT NULL, 
  "msg" text NOT NULL, 
  "user_id" bigint DEFAULT 0 NOT NULL, 
  PRIMARY KEY ("id") 
);

INSERT INTO "#__shoutbox" ("name", "when", "msg", "user_id") VALUES ('JoomJunk', '2013-04-04 20:00:00', 'Welcome to the Shoutbox', '0');
