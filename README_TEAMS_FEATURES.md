# ETW Class Teams Update

This version adds Microsoft Teams-style subject hubs.

## Student
- My Teams appears in the profile-picture dropdown.
- Every approved subject offering becomes its own team.
- Each team has Overview, Assignments, and Meetings tabs.
- Students can view assignments, submit text/files, see grades and teacher feedback.
- Students can join scheduled group-call meetings.

## Faculty / Professor
- Class Teams appears in the profile-picture dropdown.
- Each teaching offering becomes a team.
- Faculty can create assignments with instructions, due date, and points.
- Faculty can review submissions, enter grades, and send feedback.
- Faculty can create group-call meetings for a specific subject team.
- Students enrolled in the team are notified about new assignments and meetings.

## Online meetings
The prototype creates a unique Jitsi Meet room for each scheduled meeting. Clicking Start / Join or Join Meeting opens the group call in a new browser tab.

## Database
No manual database re-import is required when updating an existing installation because config.php automatically creates the new tables. A fresh installation can import the updated database.sql.
