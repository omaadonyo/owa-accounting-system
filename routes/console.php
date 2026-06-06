<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:database')->daily()->withoutOverlapping();
