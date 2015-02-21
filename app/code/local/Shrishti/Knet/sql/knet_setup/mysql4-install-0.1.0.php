<?php

	$this->startSetup();
	$this->run("
CREATE TABLE knet (
id int(11) NOT NULL auto_increment,
payment_id varchar(225) NOT NULL,
amount float(10,3) NOT NULL,
date datetime NOT NULL,
track_id varchar(225) NOT NULL,
udf1 int(11) NOT NULL,
udf2 int(11) NOT NULL,
transaction_id int(11),
auth int(11) NOT NULL,
reference_id varchar(225) NOT NULL DEFAULT 'null',
result enum('PRESENTED','CAPTURED','NOT CAPTURED','CANCELED') NOT NULL DEFAULT 'PRESENTED',
udf3 varchar(225) NOT NULL,
udf4 varchar(225) NOT NULL,
udf5 varchar(225) NOT NULL,
PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
	$this->endSetup();