#include "ast.h"

#include <stdlib.h>

static ASTNode *ast_allocate_node(void) {
    ASTNode *node = (ASTNode *)calloc(1, sizeof(ASTNode));
    return node;
}

ASTNode *ast_make_number(double value) {
    ASTNode *node = ast_allocate_node();
    if (node == NULL) {
        return NULL;
    }

    node->type = NODE_NUMBER;
    node->value = value;
    return node;
}

ASTNode *ast_make_binary(OperatorType op, ASTNode *left, ASTNode *right) {
    ASTNode *node = ast_allocate_node();
    if (node == NULL) {
        ast_free(left);
        ast_free(right);
        return NULL;
    }

    node->type = NODE_BINARY;
    node->op = op;
    node->left = left;
    node->right = right;
    return node;
}

void ast_free(ASTNode *node) {
    if (node == NULL) {
        return;
    }

    ast_free(node->left);
    ast_free(node->right);
    free(node);
}

const char *ast_operator_symbol(OperatorType op) {
    switch (op) {
        case OP_ADD:
            return "+";
        case OP_SUBTRACT:
            return "-";
        case OP_MULTIPLY:
            return "*";
        case OP_DIVIDE:
            return "/";
        default:
            return "?";
    }
}

const char *ast_operator_name(OperatorType op) {
    switch (op) {
        case OP_ADD:
            return "Add";
        case OP_SUBTRACT:
            return "Subtract";
        case OP_MULTIPLY:
            return "Multiply";
        case OP_DIVIDE:
            return "Divide";
        default:
            return "Unknown";
    }
}
