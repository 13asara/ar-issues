<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:prune-failed --hours=720')->weekly();
