# Pre-Production Checklist

This checklist ensures the new paper evaluation storage system is ready for production deployment.

## ✅ Development Phase (Completed)

- [x] Database migration created and tested
- [x] PaperEvaluation model implemented
- [x] ProcessPaperEvaluation job created
- [x] Legacy job renamed (ProcessEvaluationLegacy)
- [x] Controller updated to use new job
- [x] UI enhanced with real-time updates
- [x] Factory created for testing
- [x] Test suite created (10 tests, all passing)
- [x] Code formatted with Pint
- [x] Documentation written
- [x] Migration guide created
- [x] Implementation summary documented

## 🔍 Pre-Testing Phase

Before testing in development environment:

- [ ] Ensure Reverb is running (`php artisan reverb:start`)
- [ ] Verify queue worker is running (`php artisan queue:work`)
- [ ] Check Docker container is running (`docker ps | grep training-and-ms`)
- [ ] Verify Docker volume mappings for input/output folders
- [ ] Test database connection
- [ ] Run migrations on test database (`php artisan migrate`)

## 🧪 Testing Phase

### Unit Tests
- [ ] Run all tests: `php artisan test`
- [ ] Run specific test: `php artisan test tests/Feature/ProcessPaperEvaluationTest.php`
- [ ] Verify all 10 tests pass
- [ ] Check test coverage is adequate

### Manual Testing
- [ ] Navigate to `/evaluations/cargar-evaluacion`
- [ ] Upload a test PDF with evaluations
- [ ] Verify real-time status updates appear
- [ ] Check upload progress bar works
- [ ] Verify processing status changes
- [ ] Confirm completion message appears
- [ ] Check database for new records in `paper_evaluations`
- [ ] Verify data structure matches evaluation type
- [ ] Test with each evaluation type (01, 02, 03, 04)
- [ ] Test error handling with invalid PDF

### Data Verification
- [ ] Check folio parsing: `PaperEvaluation::parseFolio('019530001')`
- [ ] Verify organization lookup/creation
- [ ] Confirm structured data extraction
- [ ] Validate JSON fields contain expected data
- [ ] Check `raw_data` preserves original OCR output
- [ ] Verify timestamps are correct
- [ ] Test soft deletes work

### Query Performance
- [ ] Test filtering by evaluation type
- [ ] Test filtering by source (online/paper)
- [ ] Test filtering by status
- [ ] Test composite index usage with EXPLAIN
- [ ] Verify queries are fast with sample data

### WebSocket/Reverb
- [ ] Confirm WebSocket connection establishes
- [ ] Verify status updates appear in real-time
- [ ] Test with multiple browser tabs
- [ ] Check for memory leaks with long sessions
- [ ] Test reconnection after disconnect

## 🔐 Security Review

- [ ] Verify file upload validation (PDF only, max 10MB)
- [ ] Check file storage permissions
- [ ] Ensure sensitive data is not logged
- [ ] Verify authorization on routes
- [ ] Check for SQL injection vulnerabilities (should be safe with Eloquent)
- [ ] Verify XSS prevention in UI
- [ ] Check CSRF protection on forms

## 📊 Error Handling

### Test Error Scenarios
- [ ] Upload invalid file type
- [ ] Upload file exceeding size limit
- [ ] Upload corrupted PDF
- [ ] Test Docker container unavailable
- [ ] Test OCR processing failure
- [ ] Test invalid folio format
- [ ] Test missing organization
- [ ] Verify error messages are user-friendly
- [ ] Check errors are logged properly
- [ ] Verify retry mechanism works

### Monitor Logs
- [ ] Check Laravel logs for errors
- [ ] Review Horizon for failed jobs
- [ ] Monitor Docker logs for OCR issues
- [ ] Verify all errors include context

## 🎨 UI/UX Review

- [ ] Test drag-and-drop file upload
- [ ] Test file selection via button
- [ ] Verify disabled states work correctly
- [ ] Check loading animations
- [ ] Test status icons and colors
- [ ] Verify responsive design
- [ ] Test keyboard navigation
- [ ] Check accessibility (ARIA labels)
- [ ] Test with different screen sizes

## 📝 Documentation Review

- [ ] Read through `PAPER_EVALUATION_STORAGE.md`
- [ ] Review `PAPER_EVALUATION_MIGRATION_GUIDE.md`
- [ ] Verify code examples work
- [ ] Check documentation is up-to-date
- [ ] Ensure all API methods are documented
- [ ] Verify diagrams and examples are correct

## 🚀 Pre-Production Steps

### Code Review
- [ ] Review all new files for code quality
- [ ] Check for TODO comments that need addressing
- [ ] Verify error messages are translatable if i18n is used
- [ ] Ensure no sensitive data in code
- [ ] Check for unused imports/variables

### Database
- [ ] Backup existing database
- [ ] Test migration on staging database
- [ ] Verify rollback works: `php artisan migrate:rollback`
- [ ] Re-run migration: `php artisan migrate`
- [ ] Check for migration conflicts

### Performance
- [ ] Test with large PDFs (multiple pages)
- [ ] Test with multiple simultaneous uploads
- [ ] Monitor memory usage during processing
- [ ] Check database query count (N+1 issues)
- [ ] Verify indexes are being used

### Dependencies
- [ ] Ensure all Composer dependencies are installed
- [ ] Ensure all NPM dependencies are installed
- [ ] Verify Docker image is up-to-date
- [ ] Check Python dependencies in container

## 📋 Production Deployment

### Pre-Deployment
- [ ] Create deployment plan
- [ ] Schedule maintenance window if needed
- [ ] Notify stakeholders
- [ ] Prepare rollback plan
- [ ] Backup production database

### Deployment Steps
- [ ] Pull latest code from branch
- [ ] Run `composer install --optimize-autoloader --no-dev`
- [ ] Run `npm run build`
- [ ] Run `php artisan migrate --force`
- [ ] Clear caches: `php artisan optimize:clear`
- [ ] Optimize: `php artisan optimize`
- [ ] Restart queue workers
- [ ] Restart Reverb if needed

### Post-Deployment
- [ ] Verify application is accessible
- [ ] Test evaluation upload in production
- [ ] Monitor logs for errors
- [ ] Check Horizon dashboard
- [ ] Verify WebSocket connections
- [ ] Test with real evaluation PDF
- [ ] Monitor performance metrics
- [ ] Verify data is being stored correctly

## 🔄 Post-Production

### Monitoring (First 24 Hours)
- [ ] Monitor error rates
- [ ] Check job success/failure rates
- [ ] Review processing times
- [ ] Monitor database growth
- [ ] Check server resources (CPU, RAM, disk)
- [ ] Review user feedback

### Monitoring (First Week)
- [ ] Analyze usage patterns
- [ ] Review all error logs
- [ ] Check for performance degradation
- [ ] Verify data integrity
- [ ] Gather user feedback
- [ ] Document any issues

### Optimization (As Needed)
- [ ] Tune database indexes based on actual usage
- [ ] Optimize slow queries
- [ ] Adjust job timeout if needed
- [ ] Scale resources if necessary

## 📞 Support

### Team Preparation
- [ ] Train support team on new features
- [ ] Document common issues and solutions
- [ ] Create troubleshooting guide
- [ ] Set up monitoring alerts
- [ ] Establish escalation procedures

### User Communication
- [ ] Prepare user announcement
- [ ] Update user documentation
- [ ] Create tutorial/guide if needed
- [ ] Prepare FAQ
- [ ] Set up feedback channel

## ✅ Sign-Off

Before marking as production-ready:

- [ ] All tests passing
- [ ] Code reviewed and approved
- [ ] Documentation complete
- [ ] Performance acceptable
- [ ] Security verified
- [ ] Stakeholders informed
- [ ] Rollback plan ready
- [ ] Support team trained

**Approved by:** _________________  
**Date:** _________________  

---

## Notes

Use this space to document any issues found during testing or special considerations:

```
[Add notes here]
```

## Issues Log

| Date | Issue | Resolution | Status |
|------|-------|------------|--------|
|      |       |            |        |
