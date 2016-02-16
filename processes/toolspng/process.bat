@echo off

if exist output (
    rd /S/Q output
)

php process.php

:end