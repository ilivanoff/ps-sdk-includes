@echo off

rd /S/Q output
rem rd /S/Q source

if not exist source (
    md source
)
