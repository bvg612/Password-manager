<?php

$db->query('
  CREATE TABLE IF NOT EXISTS vars(
      name VARCHAR(32),
      value TEXT,
      PRIMARY KEY(name)
  )
');
