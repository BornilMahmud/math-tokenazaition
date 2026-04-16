#ifndef MATHLANG_AST_H
#define MATHLANG_AST_H

#include <stddef.h>

typedef enum {
    NODE_NUMBER,
    NODE_BINARY
} NodeType;

typedef enum {
    OP_ADD,
    OP_SUBTRACT,
    OP_MULTIPLY,
    OP_DIVIDE
} OperatorType;

typedef struct ASTNode {
    NodeType type;
    OperatorType op;
    double value;
    struct ASTNode *left;
    struct ASTNode *right;
} ASTNode;

ASTNode *ast_make_number(double value);
ASTNode *ast_make_binary(OperatorType op, ASTNode *left, ASTNode *right);
void ast_free(ASTNode *node);
const char *ast_operator_symbol(OperatorType op);
const char *ast_operator_name(OperatorType op);

#endif
