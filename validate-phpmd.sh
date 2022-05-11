#!/bin/bash

# Parse input params
while getopts "f:" opt; do
  case $opt in
    f) VALIDATION_FOLDER="$OPTARG"
    ;;
    \?) echo "Invalid option -$OPTARG" >&2
    ;;
  esac
done

# Default folder/file to validate
[ -z "$VALIDATION_FOLDER" ] && VALIDATION_FOLDER="app/code/Retailplace"

if [[ -d "$VALIDATION_FOLDER" || -f "$VALIDATION_FOLDER" ]]; then
  php vendor/phpmd/phpmd/src/bin/phpmd \
      "$VALIDATION_FOLDER" text pipeline/tests/static/phpmd/ruleset.xml --suffixes=php

  if [ $? != 0 ]; then
    echo "PHPMD validation failed!"
    exit 1
  fi
fi

echo "PHPMD validation completed!"
