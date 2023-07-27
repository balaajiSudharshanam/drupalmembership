<?php

namespace Drupal\role_expire\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\rules\Exception\InvalidArgumentException;
use Drupal\user\UserInterface;

/**
 * Provides a 'Set expire time' action.
 *
 * @RulesAction(
 *   id = "role_expire_set_expire_time",
 *   label = @Translation("Set expire time for user roles"),
 *   category = @Translation("Role expire"),
 *   context_definitions = {
 *     "user" = @ContextDefinition("entity:user",
 *       label = @Translation("User")
 *     ),
 *     "roles" = @ContextDefinition("string",
 *       label = @Translation("Roles ID"),
 *       multiple = TRUE
 *     ),
 *     "date" = @ContextDefinition("string",
 *       label = @Translation("Roles expiry date"),
 *       description = @Translation("Enter date and time in format <em>YYYY-MM-DD HH:MM:SS</em> or use relative time i.e. 1 day, 2 months, 1 year, 3 years.")
 *     )
 *   }
 * )
 */
class RoleExpireSetExpireTime extends RulesActionBase {

  /**
   * Assign expire time for user and role.
   *
   * @param \Drupal\user\UserInterface $user
   *   User object.
   * @param string $roles
   *   Array of User roles ID.
   * @param string $date
   *   Date when the roles will expire.
   *
   * @throws \Drupal\rules\Exception\InvalidArgumentException
   */
  protected function doExecute(UserInterface $user, array $roles, $date) {
    foreach ($roles as $role) {
      // Skip adding the expire time for the role if user doesn't have it.
      if ($user->hasRole($role)) {
        try {
          $time = strtotime($date);
          if (!empty($time)) {
            \Drupal::service('role_expire.api')->writeRecord($user->id(), $role, $time);
          }
          else {
            throw new InvalidArgumentException($this->t('Invalid date format.'));
          }
        }
        catch (\InvalidArgumentException $e) {
          throw new InvalidArgumentException($e->getMessage());
        }
      }
    }
  }

}
