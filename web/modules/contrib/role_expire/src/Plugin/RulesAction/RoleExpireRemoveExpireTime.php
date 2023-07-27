<?php

namespace Drupal\role_expire\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\rules\Exception\InvalidArgumentException;
use Drupal\user\UserInterface;

/**
 * Provides a 'Remove expire time' action.
 *
 * @RulesAction(
 *   id = "role_expire_remove_expire_time",
 *   label = @Translation("Remove expire time for user roles"),
 *   category = @Translation("Role expire"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User")
 *     ),
 *     "roles" = @ContextDefinition("string",
 *       label = @Translation("Roles ID"),
 *       multiple = TRUE
 *     )
 *   }
 * )
 */
class RoleExpireRemoveExpireTime extends RulesActionBase {

  /**
   * Assign expire time for user and role.
   *
   * @param \Drupal\user\UserInterface $user
   *   User object.
   * @param string $roles
   *   Array of User roles ID.
   *
   * @throws \Drupal\rules\Exception\InvalidArgumentException
   */
  protected function doExecute(UserInterface $user, array $roles) {
    foreach ($roles as $role) {
      // Skip adding the expire time for the role if user doesn't have it.
      if ($user->hasRole($role)) {
        try {
          \Drupal::service('role_expire.api')->deleteRecord($user->id(), $role);
        } catch (\InvalidArgumentException $e) {
          throw new InvalidArgumentException($e->getMessage());
        }
      }
    }
  }
}
