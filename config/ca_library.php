<?php

// CA Library static values. Courses, groups and exam attempts are fixed
// business constants — deliberately NOT database tables.
return [

    'courses' => [
        'CA Foundation',
        'CA Intermediate',
        'CA Final',
    ],

    // CA Foundation has no groups (group stays NULL for it).
    'groups' => [
        'Group 1',
        'Group 2',
        'Both Groups',
    ],

    'exam_attempts' => [
        'May 2026',
        'January 2026',
        'September 2025',
        'May 2025',
        'January 2025',
        'September 2024',
        'June 2024',
        'December 2023',
        'June 2023',
        'December 2022',
        'June 2022',
    ],
];
