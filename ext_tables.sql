#
# Table structure for table 'tt_news'
#
CREATE TABLE tt_news (
	tx_socialpostd_fb_publish tinyint(3) DEFAULT '0' NOT NULL,
	tx_socialpostd_fb_ignor_publish tinyint(3) DEFAULT '0' NOT NULL,
	tx_socialpostd_tw_publish tinyint(3) DEFAULT '0' NOT NULL,
	tx_socialpostd_tw_ignor_publish tinyint(3) DEFAULT '0' NOT NULL
);