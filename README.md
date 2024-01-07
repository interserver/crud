# Advanced CRUD Class

.. because designing a webpage should be as easy as writing an sql query

its got a very customizable output and customizable field handling ,  but aiming for it to automatically
generate an optimal page w/out the need for customizing in most cases

i figure most all pages in my are based around a single query/table , each page just having basically a
different query, but essentially all doing the same basic things with it, building a list of records, a
form to add or edit them, or simply display a record, or a page might be a group of several of these
things...

so my goal w this is to automatically generate as much as possible for each page using only the given
query, and where customized handling for something is needed make that process as simple as possible as
well, so pages can be reworked and code reduced to just the fewest bits of information unique to that
page and get improved layouts+validation/form-handling as a side effect

CRUD classes themselves are very popular and used in most all big frameworks, but from extensive
research (over the past few years of trying to decide which one to use) they all seem rather hard
to implement or at least a lot of code just to setup a page using it , my approach is i think the
ideal way to do it taking the best of what ive found in other CRUD classes but requiring less code
to set it up.

since the HTML part is all handled within the templates and the class generates things like validations
and field information, its easy to setup several alternate layouts and easily choose an alternate one
when you don't want to use the default layout.  it also makes it easy to setup things like alternate
interfaces such as a CLI or ANSI Terminal GUI, Windows/OS Native programs, and even various API
interfaces with relative ease only having to add the code for basic components of each once. Although
I do have several table alternate layouts already, I have no plans to setup additional templates
until everything else with it is working and getting widely implemented.

## SQL Parsers

Here are some sql parsers to check out to find the best to use

* [greenlion/PHP-SQL-Parser: A pure PHP SQL (non validating) parser w/ focus on MySQL dialect of SQL](https://github.com/greenlion/PHP-SQL-Parser)
* [phpmyadmin/sql-parser: A validating SQL lexer and parser with a focus on MySQL dialect.](https://github.com/phpmyadmin/sql-parser)
* [iamcal/SQLParser: Parse MySQL schemas in PHP, fast](https://github.com/iamcal/SQLParser)
* [crodas/SQLParser: SQL-Parser](https://github.com/crodas/SQLParser)
* [SQLFTW/sqlftw: SQL lexer, parser, model and static analysis in PHP (for MySQL dialect)](https://github.com/SQLFTW/sqlftw)
