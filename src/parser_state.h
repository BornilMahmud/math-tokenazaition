#ifndef MATHLANG_PARSER_STATE_H
#define MATHLANG_PARSER_STATE_H

#include <stdio.h>

#include "ast.h"

typedef struct yy_buffer_state *YY_BUFFER_STATE;

extern ASTNode *g_ast_root;
extern char g_parse_error[512];
extern char g_parse_suggestion[512];

int yyparse(void);
YY_BUFFER_STATE yy_scan_string(const char *str);
void yy_delete_buffer(YY_BUFFER_STATE buffer);

void parser_reset_state(void);
void parser_set_error(const char *message, const char *suggestion);

#endif
