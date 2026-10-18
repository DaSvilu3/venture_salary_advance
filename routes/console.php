<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('advances:collect')->dailyAt('06:00')->withoutOverlapping();
