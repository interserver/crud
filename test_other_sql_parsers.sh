#!/bin/bash
base="$(readlink -f "$(dirname "$0")")";
mkdir -p "$base/sql_parsers"
for i in alesculek/php-sql-parser drecon/PHP-SQL-Parser fimbulvetr/PhpSqlParser iamcal/SQLParser ichiriac/sql-parser phpmyadmin/sql-parser soundintheory/php-sql-parser; do
  cd "$base/sql_parsers"
  echo "Working on $i"
  d="$(echo "$i" |tr "/" "-")";
  if [ ! -e "$d" ]; then
    git clone "https://github.com/$i" "$d"
  fi
  cd "$base/sql_parsers/$d"
  git pull --all
  git submodule update --init --recursive
  git submodule sync --recursive
  composer update --with-dependencies -v -o
done
