<?php
/**
 * User: npelov
 * Date: 14-05-17
 * Time: 12:04 PM
 */

namespace nsfw\users;


use nsfw\database\Database;

class ObjectPermissions {
  /** @var Database */
  protected $db;
  protected $userId;
  protected $objectId;
  protected $permissions = [];

  /**
   * ObjectPermissions constructor.
   * @param Database $db
   * @param $userId
   * @param $objectId
   */
  public function __construct(Database $db, $userId, $objectId) {
    $this->db = $db;
    $this->userId = $userId;
    $this->objectId = $objectId;
  }

  public function has($permission) {
    if(!$this->permissionExists($permission)) {
      $message = 'Permission "' . $permission . '" does not exist';
      if(!empty($this->objectId))
        $message .= ' for object '.$this->objectId;
      throw new \Exception($message);
    }
    return $this->permissions[$permission];
  }

  /**
   * @param string $permission
   * @param bool $value
   * @param bool $updateDb
   */
  public function setPermission($permission, $value, $updateDb = true) {
    $this->permissions[$permission] = $value;
  }

  /**
   * @param string $permissionName
   * @return bool true if has permission
   * @throws \Exception
   */
  protected function loadPermission($permissionName) {
    $row = $this->db->query('
      SELECT 
          permissions.id as id,
          -- permissions.name as name,
          -- permissions.object as object,
          rolesXpermissions.ref_id as ref_id -- null if no permission
        FROM permissions
          LEFT JOIN rolesXpermissions on rolesXpermissions.permission_id = permissions.id
          INNER JOIN usersXroles ON rolesXpermissions.role_id = usersXroles.role_id
           
        WHERE
          usersXroles.user_id = '.intval($this->userId).'
          AND permissions.object = '.intval($this->objectId).'
          and name = '.intval($permissionName.'
    '));
    if(!empty($row)) {
      throw new \Exception('Permission "' . $permissionName . '" does not exist');
    }

    //$permissionName = $row['name'];
    $refId = $row['ref_id'];
    $permission = new Permission($row);
    $this->permissions[$permissionName][$refId] = $permission;
    return $permission->hasPermission;
  }

  protected function updatePermission($permissionName, $refId) {
    /** @var Permission $permission */
    $permission = $this->permissions[$permissionName][$refId];
    if($permission->hasPermission) {
      $this->db->insert('ob');
    }
  }

}
