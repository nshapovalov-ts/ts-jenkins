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
  php vendor/squizlabs/php_codesniffer/bin/phpcs \
      --standard=pipeline/tests/static/phpcs/ruleset.xml --extensions=php "$VALIDATION_FOLDER"

  if [ $? != 0 ]; then
    echo "PHPCS validation failed!"
    exit 1
  fi
fi

echo "PHPCS validation completed!"
