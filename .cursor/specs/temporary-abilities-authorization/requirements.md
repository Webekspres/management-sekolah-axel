# Requirements Document: Temporary Abilities-Based Authorization

## Introduction

Sistem ini mengubah mekanisme autorisasi sementara dari "temporary role elevation" (user sementara mendapat role Guru/Kepala Sekolah/Super Admin) menjadi "abilities-based authorization" yang lebih granular dan aman. Dengan pendekatan abilities-based, user hanya mendapat permissions spesifik yang diberikan, bukan mewarisi SEMUA hak dari sebuah role. Sistem ini menggunakan Laravel policies dan abilities untuk implementasi yang lebih aman, fleksibel, dan mudah diaudit.

## Glossary

- **Temporary_Access_System**: Sistem pemberian akses sementara kepada user dengan durasi tertentu
- **Ability**: Permission spesifik untuk melakukan aksi tertentu (contoh: viewAny, create, update, delete)
- **Access_Policy**: Kebijakan akses yang mendefinisikan abilities untuk resource tertentu (contoh: KBM Policy, Lesson Plan Policy)
- **Temporary_Ability_Grant**: Record pemberian ability sementara kepada user dengan waktu kedaluwarsa
- **Role_Elevation**: Mekanisme lama yang memberikan role sementara (Guru/Kepala Sekolah/Super Admin) kepada user
- **Inherited_Ability**: Ability yang didapat user dari role permanennya
- **Direct_Ability**: Ability yang diberikan langsung kepada user (bukan dari role)
- **Super_Admin**: Role dengan hak tertinggi yang dapat memberikan temporary abilities kepada user lain
- **Temporary_Access_Management_Page**: Halaman Filament untuk mengelola pemberian akses sementara
- **User_Policy_Ability**: Model yang menyimpan abilities user (inherited atau direct)

## Requirements

### Requirement 1: Remove Temporary Role Elevation Feature

**User Story:** As a Super Admin, I want the temporary role elevation feature removed from the system, so that users cannot inherit all permissions from a role temporarily.

#### Acceptance Criteria

1. THE Temporary_Access_Management_Page SHALL NOT display the "Temporary Role Elevation (Opsional)" select field
2. THE Temporary_Access_Management_Page SHALL NOT allow selecting roles (Guru/Kepala Sekolah/Super Admin) for temporary assignment
3. THE Temporary_Access_System SHALL NOT create TemporaryRoleElevation records when granting temporary access
4. THE TemporaryAccessManager SHALL NOT resolve effective role based on TemporaryRoleElevation records
5. WHEN a Super Admin submits the temporary access form, THE Temporary_Access_System SHALL validate that at least one ability is selected (without requiring temporary role)

### Requirement 2: Implement Temporary Abilities Grant System

**User Story:** As a Super Admin, I want to grant specific abilities to users temporarily, so that users only receive the exact permissions they need without inheriting unnecessary privileges.

#### Acceptance Criteria

1. THE Temporary_Access_System SHALL create Temporary_Ability_Grant records with user_id, access_policy_id, ability, granted_by_user_id, and expires_at fields
2. WHEN a Super Admin selects abilities for a user, THE Temporary_Access_System SHALL store each ability as a separate Temporary_Ability_Grant record
3. THE Temporary_Ability_Grant SHALL have an expires_at timestamp that determines when the ability expires
4. THE Temporary_Access_System SHALL support duration options: 1 day, 1 week, 1 month, 1 year, and custom date
5. WHEN a Temporary_Ability_Grant expires, THE Temporary_Access_System SHALL NOT grant the ability to the user

### Requirement 3: Integrate Temporary Abilities with Laravel Policies

**User Story:** As a developer, I want temporary abilities to work seamlessly with Laravel policies, so that authorization checks automatically consider temporary grants.

#### Acceptance Criteria

1. WHEN a policy checks user authorization, THE TemporaryAccessManager SHALL check for active Temporary_Ability_Grant records before denying access
2. THE TemporaryAccessManager SHALL consider a Temporary_Ability_Grant active WHEN expires_at is greater than current time
3. WHEN a user has a temporary ability grant for a specific ability and policy, THE policy SHALL authorize the action
4. THE TemporaryAccessManager SHALL return true for hasTemporaryAbility(user, policy, ability) WHEN an active Temporary_Ability_Grant exists
5. THE policy authorization check SHALL prioritize inherited abilities, then direct abilities, then temporary abilities

### Requirement 4: Update Temporary Access Management UI

**User Story:** As a Super Admin, I want a clear interface to grant temporary abilities, so that I can easily assign specific permissions to users.

#### Acceptance Criteria

1. THE Temporary_Access_Management_Page SHALL display a section titled "Policies & Abilities" with checkboxes for each ability
2. THE Temporary_Access_Management_Page SHALL group abilities by Access_Policy (KBM, Lesson Plan, Announcement, etc.)
3. THE Temporary_Access_Management_Page SHALL disable checkboxes for abilities that are inherited from the user's role
4. THE Temporary_Access_Management_Page SHALL display helper text explaining that disabled abilities are inherited from role
5. WHEN a Super Admin selects users and abilities, THE Temporary_Access_Management_Page SHALL show a count of abilities to be granted
6. THE Temporary_Access_Management_Page SHALL display success notification showing count of abilities granted after submission

### Requirement 5: Validate Temporary Ability Grants

**User Story:** As a Super Admin, I want the system to validate temporary ability grants, so that only valid abilities are assigned and errors are prevented.

#### Acceptance Criteria

1. WHEN a Super Admin submits the form without selecting any abilities, THE Temporary_Access_System SHALL display an error message "Silakan pilih minimal satu ability"
2. THE Temporary_Access_System SHALL NOT create Temporary_Ability_Grant records for abilities that are already inherited from the user's role
3. WHEN an ability is already inherited, THE Temporary_Access_System SHALL increment an inherited_ability_count and skip creation
4. THE Temporary_Access_System SHALL validate that the selected ability exists in the Access_Policy's available abilities
5. WHEN a Temporary_Ability_Grant creation fails, THE Temporary_Access_System SHALL collect the error and display it in a warning notification

### Requirement 6: Query and Display Active Temporary Abilities

**User Story:** As a Super Admin, I want to view active temporary abilities for users, so that I can audit and manage temporary access grants.

#### Acceptance Criteria

1. THE Temporary_Access_System SHALL provide a method to query active Temporary_Ability_Grant records for a specific user
2. THE Temporary_Ability_Grant query SHALL filter by expires_at greater than current time to return only active grants
3. THE Temporary_Access_Management_Page SHALL display a list of active temporary abilities with user name, policy name, ability, granted by, and expiration date
4. THE Temporary_Access_System SHALL allow Super_Admin to revoke a Temporary_Ability_Grant before expiration
5. WHEN a Temporary_Ability_Grant is revoked, THE Temporary_Access_System SHALL delete the record immediately

### Requirement 7: Cleanup Expired Temporary Abilities

**User Story:** As a system administrator, I want expired temporary abilities to be automatically cleaned up, so that the database does not accumulate stale records.

#### Acceptance Criteria

1. THE Temporary_Access_System SHALL provide a scheduled command to delete expired Temporary_Ability_Grant records
2. THE cleanup command SHALL run daily at midnight
3. THE cleanup command SHALL delete Temporary_Ability_Grant records WHERE expires_at is less than or equal to current time
4. THE cleanup command SHALL log the count of deleted records
5. THE cleanup command SHALL complete within 60 seconds for databases with up to 100,000 expired records

### Requirement 8: Maintain Backward Compatibility with Existing Policies

**User Story:** As a developer, I want existing policies to continue working without modification, so that the migration to abilities-based authorization is seamless.

#### Acceptance Criteria

1. THE TemporaryAccessManager SHALL maintain the hasTemporaryPolicyGrant method signature for backward compatibility
2. THE hasTemporaryPolicyGrant method SHALL check for active Temporary_Ability_Grant records instead of TemporaryRoleElevation
3. THE existing policies (KbmPolicy, LessonPlanPolicy, AnnouncementPolicy) SHALL continue to call hasTemporaryAccess without modification
4. THE TemporaryAccessManager SHALL resolve target model class from policy arguments correctly
5. WHEN a policy calls hasTemporaryAccess, THE TemporaryAccessManager SHALL return true if an active Temporary_Ability_Grant exists for the user, policy, and ability

### Requirement 9: Migration from Role Elevation to Abilities

**User Story:** As a system administrator, I want to migrate existing temporary role elevations to temporary abilities, so that users do not lose their temporary access during the transition.

#### Acceptance Criteria

1. THE migration system SHALL provide a command to convert existing TemporaryRoleElevation records to Temporary_Ability_Grant records
2. WHEN converting a TemporaryRoleElevation, THE migration SHALL create Temporary_Ability_Grant records for all abilities that the elevated role would have
3. THE migration SHALL preserve the expires_at timestamp from the original TemporaryRoleElevation
4. THE migration SHALL set granted_by_user_id from the original TemporaryRoleElevation record
5. WHEN migration is complete, THE migration command SHALL log the count of converted records and any errors

### Requirement 10: Audit Trail for Temporary Ability Grants

**User Story:** As a Super Admin, I want to track who granted temporary abilities and when, so that I can audit access control changes.

#### Acceptance Criteria

1. THE Temporary_Ability_Grant SHALL store granted_by_user_id to track who granted the ability
2. THE Temporary_Ability_Grant SHALL store created_at timestamp to track when the ability was granted
3. THE Temporary_Access_System SHALL provide a method to query all Temporary_Ability_Grant records granted by a specific user
4. THE Temporary_Access_Management_Page SHALL display granted_by user name in the active abilities list
5. THE Temporary_Access_System SHALL log ability grant events with user_id, policy, ability, granted_by, and expires_at to Laravel log

### Requirement 11: Performance Optimization for Ability Checks

**User Story:** As a developer, I want ability checks to be performant, so that authorization does not slow down the application.

#### Acceptance Criteria

1. THE TemporaryAccessManager SHALL eager load Access_Policy relationships when checking multiple abilities
2. THE Temporary_Ability_Grant query SHALL use database indexes on user_id, access_policy_id, ability, and expires_at columns
3. THE ability check SHALL complete within 50 milliseconds for a user with up to 100 temporary ability grants
4. THE TemporaryAccessManager SHALL cache active Temporary_Ability_Grant records for a user for 5 minutes
5. WHEN a new Temporary_Ability_Grant is created, THE cache for the affected user SHALL be invalidated

### Requirement 12: Testing Requirements

**User Story:** As a developer, I want comprehensive tests for the abilities-based authorization system, so that I can ensure correctness and prevent regressions.

#### Acceptance Criteria

1. THE test suite SHALL include feature tests for granting temporary abilities through the Temporary_Access_Management_Page
2. THE test suite SHALL include unit tests for TemporaryAccessManager methods (hasTemporaryAbility, assignAbility, revokeAbility)
3. THE test suite SHALL include policy tests verifying that temporary abilities grant access correctly
4. THE test suite SHALL include tests for expired ability cleanup command
5. THE test suite SHALL include tests for migration from TemporaryRoleElevation to Temporary_Ability_Grant
6. THE test suite SHALL verify that inherited abilities are not duplicated as temporary grants
7. THE test suite SHALL verify that temporary abilities expire correctly based on expires_at timestamp
