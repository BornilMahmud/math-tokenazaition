#include "solver.h"
#include "parser_state.h"

#include <ctype.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

ASTNode *g_ast_root = NULL;
char g_parse_error[512] = "";
char g_parse_suggestion[512] = "";

static char **g_tokens = NULL;
static size_t g_token_count = 0;
static size_t g_token_capacity = 0;

static char *solver_strdup(const char *text) {
    if (text == NULL) {
        return NULL;
    }

    size_t length = strlen(text);
    char *copy = (char *)malloc(length + 1);
    if (copy == NULL) {
        return NULL;
    }

    memcpy(copy, text, length + 1);
    return copy;
}

static void free_string_array(char **items, size_t count) {
    if (items == NULL) {
        return;
    }

    for (size_t index = 0; index < count; ++index) {
        free(items[index]);
    }
    free(items);
}

static int ensure_token_capacity(size_t needed) {
    if (needed <= g_token_capacity) {
        return 1;
    }

    size_t capacity = g_token_capacity == 0 ? 8 : g_token_capacity;
    while (capacity < needed) {
        capacity *= 2;
    }

    char **items = (char **)realloc(g_tokens, capacity * sizeof(char *));
    if (items == NULL) {
        return 0;
    }

    g_tokens = items;
    g_token_capacity = capacity;
    return 1;
}

void solver_record_token(const char *token_name, const char *lexeme) {
    char buffer[256];
    if (lexeme == NULL || lexeme[0] == '\0') {
        snprintf(buffer, sizeof(buffer), "%s", token_name);
    } else {
        snprintf(buffer, sizeof(buffer), "%s(%s)", token_name, lexeme);
    }

    if (!ensure_token_capacity(g_token_count + 1)) {
        return;
    }

    g_tokens[g_token_count] = solver_strdup(buffer);
    if (g_tokens[g_token_count] != NULL) {
        g_token_count += 1;
    }
}

void solver_reset_tokens(void) {
    free_string_array(g_tokens, g_token_count);
    g_tokens = NULL;
    g_token_count = 0;
    g_token_capacity = 0;
}

static char *solver_format_number(double value) {
    char buffer[64];
    double rounded = (double)((long long)value);
    if (value == rounded) {
        snprintf(buffer, sizeof(buffer), "%.0f", value);
    } else {
        snprintf(buffer, sizeof(buffer), "%g", value);
    }
    return solver_strdup(buffer);
}

static void string_builder_append(char **buffer, size_t *length, size_t *capacity, const char *text) {
    size_t text_length = strlen(text);
    if (*length + text_length + 1 > *capacity) {
        size_t new_capacity = (*capacity == 0) ? 256 : *capacity;
        while (*length + text_length + 1 > new_capacity) {
            new_capacity *= 2;
        }

        char *grown = (char *)realloc(*buffer, new_capacity);
        if (grown == NULL) {
            return;
        }

        *buffer = grown;
        *capacity = new_capacity;
    }

    memcpy(*buffer + *length, text, text_length);
    *length += text_length;
    (*buffer)[*length] = '\0';
}

static void string_builder_append_char(char **buffer, size_t *length, size_t *capacity, char value) {
    char text[2] = { value, '\0' };
    string_builder_append(buffer, length, capacity, text);
}

static char *build_tree_string(const ASTNode *node, const char *prefix, int is_last) {
    char *buffer = NULL;
    size_t length = 0;
    size_t capacity = 0;

    if (node == NULL) {
        return solver_strdup("<empty>");
    }

    if (prefix == NULL) {
        prefix = "";
    }

    string_builder_append(&buffer, &length, &capacity, prefix);
    if (prefix[0] != '\0') {
        string_builder_append(&buffer, &length, &capacity, is_last ? "`-- " : "|-- ");
    }

    if (node->type == NODE_NUMBER) {
        char *value_text = solver_format_number(node->value);
        if (value_text != NULL) {
            string_builder_append(&buffer, &length, &capacity, value_text);
            free(value_text);
        }
        string_builder_append_char(&buffer, &length, &capacity, '\n');
        return buffer;
    }

    string_builder_append(&buffer, &length, &capacity, ast_operator_symbol(node->op));
    string_builder_append_char(&buffer, &length, &capacity, '\n');

    char next_prefix[128];
    snprintf(next_prefix, sizeof(next_prefix), "%s%s", prefix, prefix[0] == '\0' ? "" : (is_last ? "    " : "|   "));

    char *left_tree = build_tree_string(node->left, next_prefix, 0);
    char *right_tree = build_tree_string(node->right, next_prefix, 1);

    if (left_tree != NULL) {
        string_builder_append(&buffer, &length, &capacity, left_tree);
        free(left_tree);
    }
    if (right_tree != NULL) {
        string_builder_append(&buffer, &length, &capacity, right_tree);
        free(right_tree);
    }

    return buffer;
}

static char *build_expression_string(const ASTNode *node) {
    if (node == NULL) {
        return solver_strdup("");
    }

    if (node->type == NODE_NUMBER) {
        return solver_format_number(node->value);
    }

    char *left = build_expression_string(node->left);
    char *right = build_expression_string(node->right);
    char *buffer = NULL;
    size_t length = 0;
    size_t capacity = 0;

    string_builder_append_char(&buffer, &length, &capacity, '(');
    if (left != NULL) {
        string_builder_append(&buffer, &length, &capacity, left);
    }
    string_builder_append_char(&buffer, &length, &capacity, ' ');
    string_builder_append(&buffer, &length, &capacity, ast_operator_symbol(node->op));
    string_builder_append_char(&buffer, &length, &capacity, ' ');
    if (right != NULL) {
        string_builder_append(&buffer, &length, &capacity, right);
    }
    string_builder_append_char(&buffer, &length, &capacity, ')');

    free(left);
    free(right);
    return buffer;
}

typedef struct {
    char **items;
    size_t count;
    size_t capacity;
} StepList;

static void steps_init(StepList *steps) {
    steps->items = NULL;
    steps->count = 0;
    steps->capacity = 0;
}

static void steps_free(StepList *steps) {
    if (steps->items != NULL) {
        for (size_t index = 0; index < steps->count; ++index) {
            free(steps->items[index]);
        }
        free(steps->items);
    }
    steps->items = NULL;
    steps->count = 0;
    steps->capacity = 0;
}

static int steps_push(StepList *steps, const char *text) {
    if (steps->count + 1 > steps->capacity) {
        size_t new_capacity = steps->capacity == 0 ? 8 : steps->capacity * 2;
        char **items = (char **)realloc(steps->items, new_capacity * sizeof(char *));
        if (items == NULL) {
            return 0;
        }
        steps->items = items;
        steps->capacity = new_capacity;
    }

    steps->items[steps->count] = solver_strdup(text);
    if (steps->items[steps->count] == NULL) {
        return 0;
    }
    steps->count += 1;
    return 1;
}

static double evaluate_node(const ASTNode *node, StepList *steps, int *ok) {
    if (!*ok || node == NULL) {
        return 0.0;
    }

    if (node->type == NODE_NUMBER) {
        return node->value;
    }

    double left = evaluate_node(node->left, steps, ok);
    double right = evaluate_node(node->right, steps, ok);
    if (!*ok) {
        return 0.0;
    }

    double value = 0.0;
    const char *verb = ast_operator_name(node->op);

    switch (node->op) {
        case OP_ADD:
            value = left + right;
            break;
        case OP_SUBTRACT:
            value = left - right;
            break;
        case OP_MULTIPLY:
            value = left * right;
            break;
        case OP_DIVIDE:
            if (right == 0.0) {
                parser_set_error("Division by zero.", "Use a non-zero denominator.");
                *ok = 0;
                return 0.0;
            }
            value = left / right;
            break;
        default:
            parser_set_error("Unsupported operator.", "Use sum, difference, multiply, or divide.");
            *ok = 0;
            return 0.0;
    }

    char *left_text = solver_format_number(left);
    char *right_text = solver_format_number(right);
    char *value_text = solver_format_number(value);
    char step_buffer[256];
    snprintf(step_buffer, sizeof(step_buffer), "Step %zu: %s %s %s = %s", steps->count + 1, left_text ? left_text : "?", ast_operator_symbol(node->op), right_text ? right_text : "?", value_text ? value_text : "?");
    steps_push(steps, step_buffer);

    free(left_text);
    free(right_text);
    free(value_text);
    (void)verb;
    return value;
}

static char *json_escape(const char *text) {
    char *buffer = NULL;
    size_t length = 0;
    size_t capacity = 0;

    string_builder_append_char(&buffer, &length, &capacity, '"');
    for (const unsigned char *cursor = (const unsigned char *)text; cursor != NULL && *cursor != '\0'; ++cursor) {
        switch (*cursor) {
            case '"':
                string_builder_append(&buffer, &length, &capacity, "\\\"");
                break;
            case '\\':
                string_builder_append(&buffer, &length, &capacity, "\\\\");
                break;
            case '\b':
                string_builder_append(&buffer, &length, &capacity, "\\b");
                break;
            case '\f':
                string_builder_append(&buffer, &length, &capacity, "\\f");
                break;
            case '\n':
                string_builder_append(&buffer, &length, &capacity, "\\n");
                break;
            case '\r':
                string_builder_append(&buffer, &length, &capacity, "\\r");
                break;
            case '\t':
                string_builder_append(&buffer, &length, &capacity, "\\t");
                break;
            default:
                if (*cursor < 0x20) {
                    char escape[7];
                    snprintf(escape, sizeof(escape), "\\u%04x", *cursor);
                    string_builder_append(&buffer, &length, &capacity, escape);
                } else {
                    string_builder_append_char(&buffer, &length, &capacity, (char)*cursor);
                }
                break;
        }
    }
    string_builder_append_char(&buffer, &length, &capacity, '"');
    return buffer;
}

static void json_append_array(char **buffer, size_t *length, size_t *capacity, char **items, size_t count) {
    string_builder_append_char(buffer, length, capacity, '[');
    for (size_t index = 0; index < count; ++index) {
        char *escaped = json_escape(items[index] == NULL ? "" : items[index]);
        if (escaped != NULL) {
            string_builder_append(buffer, length, capacity, escaped);
            free(escaped);
        }
        if (index + 1 < count) {
            string_builder_append_char(buffer, length, capacity, ',');
        }
    }
    string_builder_append_char(buffer, length, capacity, ']');
}

void solver_result_init(SolverResult *result) {
    if (result == NULL) {
        return;
    }

    memset(result, 0, sizeof(*result));
}

void solver_result_free(SolverResult *result) {
    if (result == NULL) {
        return;
    }

    free(result->input);
    free(result->expression);
    free(result->tree);
    free(result->error);
    free(result->suggestion);
    free_string_array(result->steps, result->step_count);
    free_string_array(result->tokens, result->token_count);
    solver_result_init(result);
}

void parser_reset_state(void) {
    g_ast_root = NULL;
    g_parse_error[0] = '\0';
    g_parse_suggestion[0] = '\0';
}

void parser_set_error(const char *message, const char *suggestion) {
    snprintf(g_parse_error, sizeof(g_parse_error), "%s", message == NULL ? "Unknown parse error." : message);
    snprintf(g_parse_suggestion, sizeof(g_parse_suggestion), "%s", suggestion == NULL ? "Check the sentence structure." : suggestion);
}

static char **copy_tokens(size_t *out_count) {
    char **items = NULL;
    size_t count = g_token_count;
    if (count == 0) {
        *out_count = 0;
        return NULL;
    }

    items = (char **)calloc(count, sizeof(char *));
    if (items == NULL) {
        *out_count = 0;
        return NULL;
    }

    for (size_t index = 0; index < count; ++index) {
        items[index] = solver_strdup(g_tokens[index]);
    }

    *out_count = count;
    return items;
}

int solve_expression(const char *input, SolverResult *result) {
    if (input == NULL || result == NULL) {
        return 0;
    }

    solver_result_init(result);
    parser_reset_state();
    solver_reset_tokens();

    result->input = solver_strdup(input);

    YY_BUFFER_STATE buffer = yy_scan_string(input);
    int parse_code = yyparse();
    yy_delete_buffer(buffer);

    result->tokens = copy_tokens(&result->token_count);

    if (parse_code != 0 || g_parse_error[0] != '\0' || g_ast_root == NULL) {
        result->ok = 0;
        result->error = solver_strdup(g_parse_error[0] == '\0' ? "Unable to parse the input." : g_parse_error);
        if (g_parse_suggestion[0] != '\0') {
            result->suggestion = solver_strdup(g_parse_suggestion);
        } else {
            result->suggestion = solver_strdup("Try a simpler sentence such as 'sum of 5 and 3'.");
        }
        ast_free(g_ast_root);
        g_ast_root = NULL;
        solver_reset_tokens();
        return 0;
    }

    StepList steps;
    steps_init(&steps);
    int ok = 1;
    double value = evaluate_node(g_ast_root, &steps, &ok);

    result->ok = ok;
    result->result = value;
    result->expression = build_expression_string(g_ast_root);
    result->tree = build_tree_string(g_ast_root, "", 1);

    result->step_count = steps.count;
    result->steps = steps.items;
    steps.items = NULL;
    steps.count = 0;
    steps.capacity = 0;

    if (!ok) {
        result->error = solver_strdup(g_parse_error[0] == '\0' ? "Evaluation failed." : g_parse_error);
        if (g_parse_suggestion[0] != '\0') {
            result->suggestion = solver_strdup(g_parse_suggestion);
        }
    }

    ast_free(g_ast_root);
    g_ast_root = NULL;
    solver_reset_tokens();
    steps_free(&steps);
    return ok;
}

char *solver_result_to_json(const SolverResult *result) {
    if (result == NULL) {
        return solver_strdup("{\"ok\":false,\"error\":\"No result\"}");
    }

    char *buffer = NULL;
    size_t length = 0;
    size_t capacity = 0;
    char result_text[64];
    snprintf(result_text, sizeof(result_text), "%g", result->result);

    string_builder_append_char(&buffer, &length, &capacity, '{');

    string_builder_append(&buffer, &length, &capacity, "\"ok\":");
    string_builder_append(&buffer, &length, &capacity, result->ok ? "true" : "false");
    string_builder_append_char(&buffer, &length, &capacity, ',');

    string_builder_append(&buffer, &length, &capacity, "\"input\":");
    char *escaped_input = json_escape(result->input == NULL ? "" : result->input);
    string_builder_append(&buffer, &length, &capacity, escaped_input == NULL ? "\"\"" : escaped_input);
    free(escaped_input);
    string_builder_append_char(&buffer, &length, &capacity, ',');

    string_builder_append(&buffer, &length, &capacity, "\"expression\":");
    char *escaped_expression = json_escape(result->expression == NULL ? "" : result->expression);
    string_builder_append(&buffer, &length, &capacity, escaped_expression == NULL ? "\"\"" : escaped_expression);
    free(escaped_expression);
    string_builder_append_char(&buffer, &length, &capacity, ',');

    string_builder_append(&buffer, &length, &capacity, "\"tree\":");
    char *escaped_tree = json_escape(result->tree == NULL ? "" : result->tree);
    string_builder_append(&buffer, &length, &capacity, escaped_tree == NULL ? "\"\"" : escaped_tree);
    free(escaped_tree);
    string_builder_append_char(&buffer, &length, &capacity, ',');

    string_builder_append(&buffer, &length, &capacity, "\"result\":");
    string_builder_append(&buffer, &length, &capacity, result_text);
    string_builder_append_char(&buffer, &length, &capacity, ',');

    string_builder_append(&buffer, &length, &capacity, "\"error\":");
    char *escaped_error = json_escape(result->error == NULL ? "" : result->error);
    string_builder_append(&buffer, &length, &capacity, escaped_error == NULL ? "\"\"" : escaped_error);
    free(escaped_error);
    string_builder_append_char(&buffer, &length, &capacity, ',');

    string_builder_append(&buffer, &length, &capacity, "\"suggestion\":");
    char *escaped_suggestion = json_escape(result->suggestion == NULL ? "" : result->suggestion);
    string_builder_append(&buffer, &length, &capacity, escaped_suggestion == NULL ? "\"\"" : escaped_suggestion);
    free(escaped_suggestion);
    string_builder_append_char(&buffer, &length, &capacity, ',');

    string_builder_append(&buffer, &length, &capacity, "\"steps\":");
    json_append_array(&buffer, &length, &capacity, result->steps, result->step_count);
    string_builder_append_char(&buffer, &length, &capacity, ',');

    string_builder_append(&buffer, &length, &capacity, "\"tokens\":");
    json_append_array(&buffer, &length, &capacity, result->tokens, result->token_count);

    string_builder_append_char(&buffer, &length, &capacity, '}');
    return buffer;
}
