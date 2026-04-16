%{
#include <stdio.h>
#include <stdlib.h>

#include "ast.h"
#include "parser_state.h"

int yylex(void);
void yyerror(const char *message);
%}

%error-verbose

%union {
    double number;
    ASTNode *node;
}

%token <number> NUMBER
%token SUM DIFFERENCE PRODUCT QUOTIENT
%token ADD SUBTRACT MULTIPLY DIVIDE
%token OF BETWEEN AND WITH BY THEN

%type <node> input expression

%left ADD SUBTRACT
%left MULTIPLY DIVIDE
%left THEN

%%
input:
    expression {
        g_ast_root = $1;
    }
;

expression:
      NUMBER {
          $$ = ast_make_number($1);
      }
    | '(' expression ')' {
          $$ = $2;
      }
    | expression ADD expression {
          $$ = ast_make_binary(OP_ADD, $1, $3);
      }
    | expression SUBTRACT expression {
          $$ = ast_make_binary(OP_SUBTRACT, $1, $3);
      }
    | expression MULTIPLY expression {
          $$ = ast_make_binary(OP_MULTIPLY, $1, $3);
      }
    | expression DIVIDE expression {
          $$ = ast_make_binary(OP_DIVIDE, $1, $3);
      }
    | expression MULTIPLY BY expression {
          $$ = ast_make_binary(OP_MULTIPLY, $1, $4);
      }
    | expression DIVIDE BY expression {
          $$ = ast_make_binary(OP_DIVIDE, $1, $4);
      }
    | expression THEN ADD expression {
          $$ = ast_make_binary(OP_ADD, $1, $4);
      }
    | expression THEN SUBTRACT expression {
          $$ = ast_make_binary(OP_SUBTRACT, $1, $4);
      }
    | expression THEN MULTIPLY expression {
          $$ = ast_make_binary(OP_MULTIPLY, $1, $4);
      }
    | expression THEN DIVIDE expression {
          $$ = ast_make_binary(OP_DIVIDE, $1, $4);
      }
    | ADD expression AND expression {
          $$ = ast_make_binary(OP_ADD, $2, $4);
      }
    | SUBTRACT expression AND expression {
          $$ = ast_make_binary(OP_SUBTRACT, $2, $4);
      }
    | SUM OF expression AND expression {
          $$ = ast_make_binary(OP_ADD, $3, $5);
      }
    | DIFFERENCE BETWEEN expression AND expression {
          $$ = ast_make_binary(OP_SUBTRACT, $3, $5);
      }
    | PRODUCT OF expression AND expression {
          $$ = ast_make_binary(OP_MULTIPLY, $3, $5);
      }
    | QUOTIENT OF expression AND expression {
          $$ = ast_make_binary(OP_DIVIDE, $3, $5);
      }
    | MULTIPLY expression WITH expression {
          $$ = ast_make_binary(OP_MULTIPLY, $2, $4);
      }
    | MULTIPLY expression BY expression {
          $$ = ast_make_binary(OP_MULTIPLY, $2, $4);
      }
    | DIVIDE expression BY expression {
          $$ = ast_make_binary(OP_DIVIDE, $2, $4);
      }
    | DIVIDE expression WITH expression {
          $$ = ast_make_binary(OP_DIVIDE, $2, $4);
      }
;

%%

void yyerror(const char *message) {
    if (message == NULL || message[0] == '\0') {
        parser_set_error("Incomplete expression.", "Add a number or a connecting word after the operator.");
        return;
    }

    parser_set_error(message, "Check the sentence for a missing number or operator.");
}
