CREATE TABLE IF NOT EXISTS `#__shoutbox` (
	`id` int(10) NOT NULL AUTO_INCREMENT,
	`name` varchar(25) NOT NULL,
	`when` TIMESTAMP NOT NULL,
	`ip` varchar(15) NOT NULL,
	`msg` text NOT NULL,

  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `#__shoutbox` (`name`, `when`, `msg`) VALUES ('JoomJunk', '2012-01-16 20:00:00', 'Welcome to the Shoutbox');
