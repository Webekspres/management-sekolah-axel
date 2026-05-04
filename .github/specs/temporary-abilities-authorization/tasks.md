# Implementation Plan: Temporary Abilities-Based Authorization

## Overview

This implementation plan transforms the temporary access system from role-based elevation to granular abilities-based authorization. The tasks are organized to build incrementally: first establishing the database and model infrastructure, then updating the service layer, followed by UI changes, and finally adding supporting commands and comprehensive testing.

## Tasks

- [ ] 1. Create database migration for temporary_ability_grants table
  - Create migration file using `php artisan make:migration create_temporary_ability_grants_table`
  - Define schema with ulid primary key, foreign keys to users and access_policies, ability string, granted_by_user_id, expires_at timestamp
  - Add indexes: (user_id, expires_at), (access_policy_id, ability), (expires_at)
  - Set up foreign key constraints with CASCADE and SET NULL behaviors
  - _Requirements: 2.1, 2.3_

- [ ] 2. Create TemporaryAbilityGrant model with relationships and scopes
  - [ ] 2.1 Create TemporaryAbilityGrant model
    - Create model using `php artisan make:model TemporaryAbilityGrant`
    - Define fillable fields: user_id, access_policy_id, ability, granted_by_user_id, expires_at
    - Set up relationships: user(), accessPolicy(), grantedBy()
    - Use ulid for primary key
    - _Requirements: 2.1, 2.2_
  
  - [ ] 2.2 Add query scopes to TemporaryAbilityGrant
    - Implement active() scope to filter by expires_at > now()
    - Implement forUser(string $userId) scope
    - Implement forPolicy(string $policyId) scope
    - Implement forAbility(string $ability) scope
    - _Requirements: 2.3, 6.2_
  
  - [ ] 2.3 Add helper methods to TemporaryAbilityGrant
    - Implement isExpired(): bool method
    - Implement revoke(): void method to delete the grant
    - _Requirements: 2.5, 6.4_
  
  - [ ]* 2.4 Write unit tests for TemporaryAbilityGrant model
    - **Property 3: Expiration Determines Active Status**
    - **Validates: Requirements 2.3, 2.5**
    - Test active() scope filters expired grants correctly
    - Test isExpired() returns correct value
    - Test relationships are defined correctly
    - Test scopes (forUser, forPolicy, forAbility) work correctly

- [ ] 3. Create TemporaryAbilityGrant factory for testing
  - Create factory using `php artisan make:factory TemporaryAbilityGrantFactory`
  - Define default state with valid user_id, access_policy_id, ability, expires_at
  - Add expired() state for testing expired grants
  - Add active() state for testing active grants
  - _Requirements: 12.1, 12.2_

- [ ] 4. Update TemporaryAccessManager service
  - [ ] 4.1 Add hasTemporaryAbility method
    - Implement hasTemporaryAbility(User $user, AccessPolicy $policy, string $ability): bool
    - Query active Temporary_Ability_Grant records with caching
    - Use cache key: "temp_abilities:{user_id}"
    - Cache TTL: 5 minutes
    - _Requirements: 3.1, 3.2, 3.4, 11.4_
  
  - [ ] 4.2 Update hasTemporaryPolicyGrant for backward compatibility
    - Modify hasTemporaryPolicyGrant to check Temporary_Ability_Grant instead of TemporaryRoleElevation
    - Resolve target model class from policy arguments
    - Find AccessPolicy by target_model
    - Query active Temporary_Ability_Grant records
    - _Requirements: 3.1, 8.1, 8.2, 8.5_
  
  - [ ] 4.3 Add assignTemporaryAbility method
    - Implement assignTemporaryAbility(User $user, AccessPolicy $policy, string $ability, User $grantedBy, CarbonInterface $expiresAt): TemporaryAbilityGrant
    - Validate ability exists in policy's abilities array
    - Check if ability is inherited from role (skip if true)
    - Create Temporary_Ability_Grant record
    - Invalidate user's cache
    - Log grant event with context
    - _Requirements: 2.1, 2.2, 5.3, 10.1, 10.2, 10.5_
  
  - [ ] 4.4 Add revokeTemporaryAbility method
    - Implement revokeTemporaryAbility(User $user, AccessPolicy $policy, string $ability): bool
    - Delete matching Temporary_Ability_Grant record
    - Invalidate user's cache
    - Log revocation event
    - _Requirements: 6.4, 6.5_
  
  - [ ] 4.5 Add getActiveTemporaryAbilities method
    - Implement getActiveTemporaryAbilities(User $user): Collection
    - Query active grants with eager loading
    - Return collection of Temporary_Ability_Grant records
    - _Requirements: 6.1, 6.2, 11.1_
  
  - [ ] 4.6 Add cleanupExpiredGrants method
    - Implement cleanupExpiredGrants(): int
    - Delete expired Temporary_Ability_Grant records
    - Return count of deleted records
    - Invalidate cache for affected users
    - _Requirements: 7.3, 7.4_
  
  - [ ] 4.7 Add cache management methods
    - Implement getCacheKey(User $user): string
    - Implement invalidateCache(User $user): void
    - Use cache tags for efficient invalidation
    - _Requirements: 11.4, 11.5_
  
  - [ ]* 4.8 Write unit tests for TemporaryAccessManager
    - **Property 1: Complete Grant Record Creation**
    - **Validates: Requirements 2.1, 10.1, 10.2**
    - Test hasTemporaryAbility returns true for active grant
    - Test hasTemporaryAbility returns false for expired grant
    - Test hasTemporaryAbility returns false for no grant
    - Test assignTemporaryAbility creates grant with all required fields
    - Test assignTemporaryAbility skips inherited ability
    - Test revokeTemporaryAbility deletes grant
    - Test cleanupExpiredGrants deletes old records
    - Test cache invalidation on grant creation
    - Test cache invalidation on revoke

- [ ] 5. Checkpoint - Ensure all tests pass
  - Run `php artisan test --compact` to verify all tests pass
  - Ensure database migration runs successfully
  - Verify model relationships work correctly
  - Ask the user if questions arise

- [ ] 6. Update User model with temporary ability methods
  - Add temporaryAbilityGrants(): HasMany relationship
  - Add hasTemporaryAbility(AccessPolicy $policy, string $ability): bool method
  - Add activeTemporaryAbilities(): Collection method
  - _Requirements: 6.1, 6.2_

- [ ] 7. Update Temporary Access Management Page
  - [ ] 7.1 Remove temporary role elevation field
    - Remove Select::make('temporary_role') field from form
    - Remove role elevation logic from submit() method
    - Remove TemporaryRoleElevation::create() calls
    - _Requirements: 1.1, 1.2, 1.3, 1.4_
  
  - [ ] 7.2 Update form validation
    - Change validation to require at least one ability (remove role requirement)
    - Add validation check in submit() method
    - Display error message: "Silakan pilih minimal satu ability"
    - _Requirements: 1.5, 5.1_
  
  - [ ] 7.3 Update submit logic to create ability grants
    - Iterate through selected users and policy_abilities
    - Check if ability is inherited using AccessPolicy::isAbilityInherited()
    - Create Temporary_Ability_Grant records for non-inherited abilities
    - Track createdCount and inheritedCount
    - _Requirements: 2.1, 2.2, 5.2, 5.3_
  
  - [ ] 7.4 Update success notification
    - Display count of abilities granted
    - Display count of inherited abilities (skipped)
    - Format: "{$createdCount} abilities granted, {$inheritedCount} inherited (skipped)"
    - _Requirements: 4.6, 5.3_
  
  - [ ] 7.5 Add UI improvements for ability selection
    - Disable checkboxes for inherited abilities
    - Add helper text: "Abilities yang di-disable adalah inherited dari role"
    - Display count of abilities to be granted before submission
    - _Requirements: 4.3, 4.4, 4.5_
  
  - [ ]* 7.6 Write feature tests for Temporary Access Management Page
    - **Property 2: One Record Per Ability**
    - **Validates: Requirements 2.2**
    - Test super admin can access page
    - Test non-super admin cannot access page
    - Test can grant temporary abilities
    - Test cannot grant without selecting abilities
    - Test inherited abilities are skipped
    - Test success notification shows correct counts
    - Test temporary role elevation field is removed
    - Test N abilities selected creates exactly N records

- [ ] 8. Update existing policies to work with new system
  - Verify KbmPolicy, LessonPlanPolicy, AnnouncementPolicy continue to work
  - Ensure hasTemporaryAccess() calls work with updated TemporaryAccessManager
  - No code changes needed (backward compatibility maintained)
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ]* 9. Write policy integration tests
  - Test temporary ability grant authorizes action
  - Test expired temporary ability does not authorize
  - Test temporary ability overrides role restriction
  - Test policy checks temporary abilities before role
  - _Requirements: 3.1, 3.2, 3.3, 12.3_

- [ ] 10. Checkpoint - Ensure all tests pass
  - Run `php artisan test --compact` to verify all tests pass
  - Test granting temporary abilities through UI
  - Verify authorization checks work correctly
  - Ask the user if questions arise

- [ ] 11. Create migration command to convert existing data
  - [ ] 11.1 Create MigrateTemporaryRoleElevationsCommand
    - Create command using `php artisan make:command MigrateTemporaryRoleElevationsCommand`
    - Set signature: 'temporary-abilities:migrate-role-elevations'
    - Set description: 'Convert existing temporary role elevations to ability grants'
    - _Requirements: 9.1_
  
  - [ ] 11.2 Implement conversion logic
    - Query active TemporaryRoleElevation records
    - For each elevation, get all policies for the elevated role
    - Create Temporary_Ability_Grant records for all abilities
    - Preserve expires_at and granted_by_user_id
    - Handle errors gracefully and log them
    - _Requirements: 9.2, 9.3, 9.4, 9.5_
  
  - [ ]* 11.3 Write tests for migration command
    - Test conversion creates correct number of grants
    - Test expires_at is preserved
    - Test granted_by_user_id is preserved
    - Test error handling for invalid data
    - _Requirements: 12.5_

- [ ] 12. Create cleanup command for expired grants
  - [ ] 12.1 Create CleanupExpiredTemporaryAbilitiesCommand
    - Create command using `php artisan make:command CleanupExpiredTemporaryAbilitiesCommand`
    - Set signature: 'temporary-abilities:cleanup'
    - Set description: 'Delete expired temporary ability grants'
    - _Requirements: 7.1_
  
  - [ ] 12.2 Implement cleanup logic
    - Query Temporary_Ability_Grant records where expires_at <= now()
    - Delete expired records
    - Log count of deleted records
    - Invalidate cache using Cache::tags('temp_abilities')->flush()
    - _Requirements: 7.3, 7.4_
  
  - [ ] 12.3 Schedule cleanup command
    - Add command to schedule in app/Console/Kernel.php or routes/console.php
    - Schedule to run daily at midnight
    - _Requirements: 7.2_
  
  - [ ]* 12.4 Write tests for cleanup command
    - Test expired grants are deleted
    - Test active grants are not deleted
    - Test command completes within 60 seconds for 100k records
    - Test cache is invalidated
    - _Requirements: 7.5, 12.4_

- [ ] 13. Add error handling and validation
  - [ ] 13.1 Add validation for ability grant creation
    - Validate user_id exists in users table
    - Validate access_policy_id exists and is active
    - Validate ability exists in policy's abilities array
    - Validate expires_at is in the future
    - _Requirements: 5.2, 5.4_
  
  - [ ] 13.2 Add error logging for grant operations
    - Log grant creation with context (user, policy, ability, granter, expiration)
    - Log grant revocation with context
    - Log validation errors with context
    - _Requirements: 10.5_
  
  - [ ] 13.3 Add user-friendly error messages
    - Display "User atau policy tidak ditemukan" for foreign key errors
    - Display "Silakan pilih minimal satu ability" for no abilities selected
    - Display validation errors for invalid ability or expired date
    - _Requirements: 5.1, 5.5_

- [ ]* 14. Write end-to-end integration tests
  - Test user without role can access with temporary ability
  - Test temporary ability expires correctly
  - Test multiple temporary abilities work together
  - Test revoking temporary ability removes access
  - _Requirements: 12.1, 12.6, 12.7_

- [ ] 15. Add performance optimizations
  - [ ] 15.1 Verify database indexes are created
    - Confirm (user_id, expires_at) index exists
    - Confirm (access_policy_id, ability) index exists
    - Confirm (expires_at) index exists
    - _Requirements: 11.2_
  
  - [ ] 15.2 Implement eager loading for relationships
    - Use with(['user', 'accessPolicy', 'grantedBy']) when querying grants
    - Avoid N+1 queries in UI and service layer
    - _Requirements: 11.1_
  
  - [ ] 15.3 Verify caching implementation
    - Confirm cache key format: "temp_abilities:{user_id}"
    - Confirm cache TTL: 5 minutes
    - Confirm cache invalidation on grant creation and revocation
    - _Requirements: 11.4, 11.5_
  
  - [ ]* 15.4 Write performance tests
    - Test authorization check completes within 50ms
    - Test cache reduces database queries
    - Test cleanup command handles 100k records within 60 seconds
    - _Requirements: 11.3, 7.5_

- [ ] 16. Run Laravel Pint to format code
  - Run `vendor/bin/pint --dirty --format agent` to format all modified PHP files
  - Ensure code matches project's expected style

- [ ] 17. Final checkpoint - Ensure all tests pass
  - Run `php artisan test --compact` to verify all tests pass
  - Run migration on test database to verify schema
  - Test granting, revoking, and expiration of temporary abilities
  - Verify UI changes work correctly
  - Verify performance targets are met
  - Ask the user if questions arise

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit and feature tests validate specific examples and edge cases
- The design uses PHP (Laravel), so all implementation will be in PHP
- Existing policies require no changes due to backward compatibility
- Migration command preserves historical data from old system
- Cleanup command runs daily to maintain database performance
