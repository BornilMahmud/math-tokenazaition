#ifndef MATHLANG_SOLVER_H
#define MATHLANG_SOLVER_H

#include <stddef.h>

typedef struct {
    int ok;
    char *input;
    char *expression;
    char *tree;
    char *error;
    char *suggestion;
    double result;
    char **steps;
    size_t step_count;
    char **tokens;
    size_t token_count;
} SolverResult;

void solver_result_init(SolverResult *result);
void solver_result_free(SolverResult *result);
int solve_expression(const char *input, SolverResult *result);
char *solver_result_to_json(const SolverResult *result);
void solver_record_token(const char *token_name, const char *lexeme);
void solver_reset_tokens(void);

#endif
