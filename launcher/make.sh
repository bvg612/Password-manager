#!/bin/bash

windres my.rc -O coff -o my.res
gcc -Wl,-subsystem,windows -s -o PasswordTool.exe exec.c my.res
