@echo off
setlocal
if not exist bin mkdir bin
pushd src
flex lexer.l
bison -d -o parser.tab.c parser.y
popd
gcc -std=c11 -O2 -Isrc src/main.c src/ast.c src/evaluator.c src/parser.tab.c src/lex.yy.c -o bin/mathlang_solver.exe
