<template>
    <!--
        HabitTrack logo — bowl + steam + leaf
        Bowl  = food logging
        Steam = freshly tracked, active habit
        Leaf  = health awareness hidden from the user, revealed in reports
    -->
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none">
        <!-- Green rounded-square background -->
        <rect width="48" height="48" rx="12" fill="#16a34a"/>

        <!-- Three steam wisps rising from the bowl -->
        <path d="M14 25 C13.4 22.8 14.6 21 14 18.5" stroke="#86efac" stroke-width="2.2" stroke-linecap="round"/>
        <path d="M24 24 C23.4 21.8 24.6 20  24 17.5" stroke="#86efac" stroke-width="2.2" stroke-linecap="round"/>
        <path d="M34 25 C33.4 22.8 34.6 21  34 18.5" stroke="#86efac" stroke-width="2.2" stroke-linecap="round"/>

        <!-- Bowl rim (flat rounded bar) -->
        <rect x="7" y="26" width="34" height="3.5" rx="1.75" fill="white"/>

        <!-- Bowl body (arc rounding to base) -->
        <path d="M7 29.5 Q7 44 24 44 Q41 44 41 29.5 Z" fill="white"/>

        <!-- Leaf inside the bowl — the health insight the user doesn't pick at log-time -->
        <path d="M24 41
                 C24 41 20 37 20.5 33.5
                 C21  31 23 30.5 24 32
                 C25 30.5 27 31   27.5 33.5
                 C28 37 24 41 24 41 Z"
              fill="#4ade80"/>

        <!-- Leaf midrib -->
        <line x1="24" y1="32" x2="24" y2="41"
              stroke="#16a34a" stroke-width="1" stroke-linecap="round" opacity="0.45"/>
    </svg>
</template>
