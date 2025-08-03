# Implementation Plan

- [x] 1. Create database migration for online_answers table





  - Create migration file with proper table structure, indexes, and foreign key constraints
  - Include all required fields: folio, personal_id, organization_id, quiz_id, question_key, answer_value, reference_guide
  - Add appropriate indexes for performance optimization
  - _Requirements: 4.1, 4.2, 4.3, 4.4_

- [x] 2. Create OnlineAnswer model





  - Create Eloquent model with proper fillable fields and relationships
  - Define relationships to Organization and Quiz models
  - Set up proper casting for answer_value field
  - _Requirements: 4.1, 4.3_

- [x] 3. Implement answer formatting helper method





  - Create formatAnswerValue method to handle different data types (arrays, booleans, strings)
  - Ensure proper JSON encoding for complex data structures
  - Handle boolean to string conversion consistently
  - _Requirements: 2.2_

- [x] 4. Implement storeOnlineAnswers method





  - Create method to process and store individual question-answer pairs
  - Handle different reference guide types (I, III, V, Cisneros)
  - Process nested answer structures (like datos_laborales)
  - Use batch insertion for performance optimization
  - _Requirements: 1.1, 1.4, 2.1, 2.2, 2.3_

- [x] 5. Update QuizController submit method





  - Remove Evaluation model creation logic
  - Remove saveOnlineAnswers method call
  - Integrate new storeOnlineAnswers method
  - Maintain existing folio and personal_id generation
  - Wrap operations in database transaction for data integrity
  - _Requirements: 1.1, 3.1, 3.2, 3.3_

- [x] 6. Update virtual folio creation logic





  - Modify createVirtualFolio method to work without evaluation_id
  - Ensure virtual folio tracking continues to work properly
  - Maintain compatibility with existing folio batch system
  - _Requirements: 3.3_

- [x] 7. Update error handling and logging





  - Ensure proper error messages are returned to users
  - Add appropriate logging for debugging purposes
  - Maintain existing error response format for frontend compatibility
  - _Requirements: 5.3_

- [x] 8. Verify quiz completion flow





  - Ensure completion page receives correct data (folio, personal_id)
  - Verify all quiz types (normal, reduced, cisneros) work correctly
  - Test that user experience remains unchanged
  - _Requirements: 5.1, 5.2, 5.4_