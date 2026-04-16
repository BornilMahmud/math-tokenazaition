$ErrorActionPreference = 'Stop'

New-Item -ItemType Directory -Force -Path (Join-Path $PSScriptRoot '..\bin') | Out-Null

Push-Location (Join-Path $PSScriptRoot '..\src')
flex lexer.l
bison -d -o parser.tab.c parser.y
Pop-Location
gcc -std=c11 -O2 -Isrc src/main.c src/ast.c src/evaluator.c src/parser.tab.c src/lex.yy.c -o bin/mathlang_solver.exe
