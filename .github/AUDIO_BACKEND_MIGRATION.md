# Audio System Backend Migration

## Description

Implement a complete backend-driven audio management system for quiz evaluations. Currently, audio URLs are hardcoded in the frontend configuration. This issue tracks the migration to a database-backed system that allows:

- Dynamic audio file associations with specific questions
- Per-organization audio customization
- Proper file upload/management interface
- Better scalability and maintainability

## Current State (Temporary Solution)

**Date**: January 4, 2026

The audio system currently uses:
- Frontend composable: `resources/js/composables/useAudioUrls.js`
- Configuration file: `config/audio.php`
- Hardcoded base URL: Configured via `AUDIO_BASE_URL` environment variable (default: `/storage/audio`)
- Fixed URL pattern: `{base_url}/{question_type}/{question_id}.mp3`

**Related Files**:
- `resources/js/Pages/Quiz/Take.vue`
- `resources/js/Pages/Quiz/TakeCisneros.vue`
- `resources/js/Pages/Quiz/TakeReduced.vue`
- `resources/js/Components/Quiz/AudioPlayer.vue`
- `config/audio.php`
- `.env.example` (AUDIO_* variables)

## Requirements

### Phase 1: Database Schema & Models
- [ ] Create `QuestionAudio` model with relationships to questions
- [ ] Create `AudioTrack` model for storing audio file metadata
- [ ] Create migration for audio tables
- [ ] Add soft deletes for audit trail

**Schema**:
```php
Table: audio_tracks
- id (UUID)
- filename
- original_filename
- mime_type
- file_size
- duration
- storage_path
- storage_disk
- created_by (FK to users)
- created_at
- updated_at
- deleted_at

Table: question_audio
- id
- question_id / question_key
- question_type (general, traumatic, cisneros, etc.)
- audio_track_id (FK)
- organization_id (nullable - for org-specific overrides)
- order
- created_at
- updated_at
```

### Phase 2: API & Controller
- [ ] Create `AudioTrackController` with CRUD operations
- [ ] Create `AudioUploadController` for file uploads
- [ ] Create `AudioDownloadController` for serving files (with access control)
- [ ] Implement proper file validation (mime type, size, duration)
- [ ] Implement soft delete recovery

**Endpoints**:
- `POST /api/audio/upload` - Upload audio file
- `GET /api/audio/{id}` - Download audio file (with access control)
- `DELETE /api/audio/{id}` - Soft delete audio
- `GET /api/questions/{id}/audio` - Get audio for specific question
- `POST /api/questions/{id}/audio` - Associate audio with question
- `DELETE /api/questions/{id}/audio` - Remove audio from question

### Phase 3: Backend Service Layer
- [ ] Create `AudioUrlService` to replace frontend composable
- [ ] Create `AudioUploadService` for file handling
- [ ] Implement audio file validation service
- [ ] Add audio caching strategy (avoid N+1 queries)

### Phase 4: Frontend Integration
- [ ] Update Quiz controllers to pass audio URLs from backend
- [ ] Remove `useAudioUrls.js` composable (after backend provides URLs)
- [ ] Update components to use backend-provided URLs
- [ ] Add upload UI for administrators (optional for Phase 1)

### Phase 5: Organization-Specific Audio
- [ ] Allow organizations to override default question audio
- [ ] Create audio management UI in organization settings
- [ ] Implement upload quota system per organization
- [ ] Add fallback mechanism (use default if org audio missing)

## Implementation Notes

### Backward Compatibility
- Keep `config/audio.php` for fallback scenarios
- Maintain current URL pattern for migration period
- Gradual migration: Per-question, then per-organization

### Security Considerations
- Validate file uploads (mime type, size, duration)
- Implement access control (only authorized users can access)
- Store files outside public directory (non-production)
- Implement virus scanning (optional)

### Performance
- Cache audio URLs with quiz data
- Eager load audio associations with questions
- Consider CDN for high-traffic scenarios
- Implement soft deletes for audit trail

### Storage Strategy
- Use Laravel's built-in storage: `Storage::disk('audio')`
- Support multiple storage drivers (local, S3, etc.)
- Implement file cleanup for deleted audio
- Add replication for disaster recovery

## Acceptance Criteria

- [ ] Backend generates audio URLs instead of frontend
- [ ] Quiz data includes audio URLs by default
- [ ] Organizations can customize audio per question
- [ ] File upload/management UI works correctly
- [ ] Audio access is properly controlled
- [ ] All existing quiz flows work without changes
- [ ] Tests pass (unit + feature)
- [ ] Documentation updated

## Estimated Effort

- Phase 1-2: 8-10 hours
- Phase 3-4: 6-8 hours
- Phase 5: 4-6 hours
- Testing & Documentation: 4-5 hours
- **Total**: 22-29 hours

## Priority

🟡 Medium - Improves scalability and flexibility but not blocking current functionality

## Related Issues

- None yet

## Checklist

- [ ] Design database schema
- [ ] Get approval on schema
- [ ] Implement database layer
- [ ] Implement service layer
- [ ] Update controllers
- [ ] Update frontend
- [ ] Write tests
- [ ] Update documentation
- [ ] Remove temporary config files
- [ ] Deploy and test in staging
