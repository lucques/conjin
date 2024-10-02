# timetable

This module allows to create and manage timetables for school classes etc. It combines timetables with logs and a syllabus which results in a definite **schedule**. You can see exactly when which topic will take place.

It results in four components.
1. **Calendar view** ("Jahreskalender"): Contains logs (past), prospective topics (future)
2. **Classlog view** ("Logbuch): Contains a list of all logs and tags with details
3. **Syllabus view** ("Stoffverteilungsplan"): Contains a list of all units with topics
4. **Progress bar**: Shows how many slots are already done (compare to current date)


## Terminology:

- **Timetable**: List of slots
    - **Slot**:        A single lesson, consists of date + timeslot
    - **Date**:        Year + Month + Day
    - **Weekday**:     0 = Monday, 1 = Tuesday, ...
    - **Timeslot**:    Time of day; 1, 2, 3, ...
    - **Week offset relative to current date**: 0 = current week, 1 = following week, ...

- **Classlog**: List of logs (attached either to a date (only for convenience the timeslot may be omitted), or to a concrete slot)
    - **Log**:
        - **Long log**: ID + Title + Content
        - **Short log**: ID + Title + Content (optional)

- **Syllabus**: List of units
    - **Unit**:      ID + Title + List of topics
    - **Topic**:     ID + Title + Number of slots

- **Schedule**: List of entries
    - `cancellation-date`
        - Represents cancellation of a whole day
        - Contains: Reason
    - `cancellation-slot`
        - Represents cancellation of a single slot
        - Contains: Reason
    - `log-long`
        - Represents a long log
    - `log-short`
        - Represents a short log
    - `topic-slot`
        - Represents a slot within a topic (A topic may span multiple slots)
        - Contains: Topic ID (refers also to unit, e.g. `myunit-mytopic`; disregards slot) + Topic object
    - `slot`
        - Represents a slot 
    - `tag`
        - Represents a **tag**
        - Contains: ID + Title