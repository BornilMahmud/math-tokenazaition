%option noyywrap nodefault yylineno

%{
#include <stdlib.h>
#include <string.h>

#include "parser_state.h"
#include "solver.h"
#include "parser.tab.h"

static void record(const char *token_name, const char *lexeme) {
    solver_record_token(token_name, lexeme);
}
%}

%%
[0-9]+(\.[0-9]+)?              {
                                    yylval.number = strtod(yytext, NULL);
                                    record("NUMBER", yytext);
                                    return NUMBER;
                                }
"sum"                          { record("SUM", yytext); return SUM; }
"difference"                   { record("DIFFERENCE", yytext); return DIFFERENCE; }
"product"                      { record("PRODUCT", yytext); return PRODUCT; }
"quotient"                     { record("QUOTIENT", yytext); return QUOTIENT; }
"add"|"plus"                   { record("ADD", yytext); return ADD; }
"subtract"|"minus"            { record("SUBTRACT", yytext); return SUBTRACT; }
"multiply"|"multiplied"|"times" { record("MULTIPLY", yytext); return MULTIPLY; }
"divide"|"divided"            { record("DIVIDE", yytext); return DIVIDE; }
"of"                           { record("OF", yytext); return OF; }
"between"                      { record("BETWEEN", yytext); return BETWEEN; }
"and"                          { record("AND", yytext); return AND; }
"with"                         { record("WITH", yytext); return WITH; }
"by"                           { record("BY", yytext); return BY; }
"then"                         { record("THEN", yytext); return THEN; }
"("                            { record("LPAREN", yytext); return '('; }
")"                            { record("RPAREN", yytext); return ')'; }
[ \t\r\n]+                    { /* skip whitespace */ }
.                               {
                                    record("UNKNOWN", yytext);
                                    parser_set_error("Unexpected character in the input.", "Remove unsupported symbols and try again.");
                                    return yytext[0];
                                }
%%
