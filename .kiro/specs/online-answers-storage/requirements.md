# Requirements Document

## Introduction

This feature involves restructuring how online quiz answers are stored by creating a dedicated `online_answers` table instead of mixing online and in-person evaluations in the same `evaluation` table. This separation will provide better data organization, clearer analytics, and prevent confusion between different evaluation types.

## Requirements

### Requirement 1

**User Story:** As a system administrator, I want online quiz answers stored separately from in-person evaluations, so that I can maintain clear data separation and avoid mixing different evaluation types.

#### Acceptance Criteria

1. WHEN an online quiz is submitted THEN the system SHALL create records in a new `online_answers` table instead of the `evaluation` table
2. WHEN storing online answers THEN the system SHALL maintain the folio identification system for traceability
3. WHEN storing online answers THEN the system SHALL preserve the personal_id for participant identification
4. WHEN storing online answers THEN the system SHALL record each individual question and answer pair separately

### Requirement 2

**User Story:** As a data analyst, I want each question-answer pair stored individually with proper metadata, so that I can perform granular analysis on quiz responses.

#### Acceptance Criteria

1. WHEN storing an answer THEN the system SHALL record the specific question identifier
2. WHEN storing an answer THEN the system SHALL record the actual answer value
3. WHEN storing an answer THEN the system SHALL record the reference guide type (I, III, V, or Cisneros)
4. WHEN storing an answer THEN the system SHALL associate it with the correct folio and personal_id

### Requirement 3

**User Story:** As a system developer, I want the new storage system to maintain compatibility with existing folio generation logic, so that the transition is seamless and doesn't break existing functionality.

#### Acceptance Criteria

1. WHEN generating folios for online quizzes THEN the system SHALL continue using the existing folio generation logic
2. WHEN generating personal_ids THEN the system SHALL continue using the existing personal_id generation logic
3. WHEN creating virtual folios THEN the system SHALL maintain the existing virtual folio creation process
4. WHEN the quiz is completed THEN the system SHALL redirect to the same completion page with the same data

### Requirement 4

**User Story:** As a database administrator, I want the new table structure to be properly designed with appropriate relationships and constraints, so that data integrity is maintained.

#### Acceptance Criteria

1. WHEN creating the online_answers table THEN it SHALL include proper foreign key relationships to organizations and quizzes
2. WHEN creating the online_answers table THEN it SHALL include appropriate indexes for performance
3. WHEN creating the online_answers table THEN it SHALL include proper data types for all fields
4. WHEN creating the online_answers table THEN it SHALL include timestamps for audit purposes

### Requirement 5

**User Story:** As a system user, I want the quiz submission process to remain unchanged from the user perspective, so that the user experience is not affected by this backend change.

#### Acceptance Criteria

1. WHEN submitting a quiz THEN the user interface SHALL remain exactly the same
2. WHEN a quiz is completed THEN the confirmation page SHALL display the same information as before
3. WHEN an error occurs during submission THEN the error handling SHALL work the same way
4. WHEN the quiz is submitted THEN the response time SHALL not be significantly impacted