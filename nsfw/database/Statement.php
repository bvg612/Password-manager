<?php
/**
 * User: npelov
 * Date: 12-11-17
 * Time: 12:15 PM
 */

namespace nsfw\database;


interface Statement {
  function getResult();

  /**
   * Binds values to fields in statement
   *
   * @param array $fields
   * @return mixed
   */
  function bind(array $fields);

  /**
   * @param array $fields If this is not empty, fields are bound before execute
   * @return mixed The result
   */
  function execute($fields = []);

  /**
   * Closes the statement
   */
  function close();
}
