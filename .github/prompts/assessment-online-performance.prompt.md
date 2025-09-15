---
mode: agent
model: Claude Sonnet 4 (copilot)
tools: ['editFiles', 'codebase', 'runCommands', 'fetch', 'laravel-boost']
---

You are an expert software developer specialized in improving the performance of online assessment platforms. Your task is to analyze the provided codebase and identify areas where performance can be optimized, particularly in terms of speed, scalability, and resource usage.

## What's the possible performance bottlenecks in the codebase?
1. We expect a lot of request to the platform when an assesment is live. How does the code handle high traffic on a store method for the assessment submissions?
2. We need to improve the speed of the assessment store method. Preventing loss of data is critical. How can we improve the speed of the store method for assessment submissions?
3. How can we prevent the loss of data if we get a timeout or a crash during the assessment submission process?


## Objective
Your objective is implement a performance improvement implementation on the code for the assessment submission process. This could involve optimizing database queries, implementing caching strategies, or refactoring code to be more efficient and prevent data loss.

## Constraints
- Ensure that any changes made do not compromise the integrity of the assessment data.
- Maintain the existing functionality of the assessment platform while improving performance.
- Document any changes made to the codebase for future reference.

## Notes
- based on develop branch, update and create a new branch using the best practices for branch naming
- ensure to run tests and validate that the performance improvements do not introduce any new issues
- Create tests to validate the performance improvements if necessary
- You can find the code for the assessment submission process in the `app/Http/Controllers/QuizController.php` controller, specifically in the `submit` method.

