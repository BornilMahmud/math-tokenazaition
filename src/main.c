#include "solver.h"

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

static char *join_arguments(int argc, char **argv, int start_index) {
    size_t length = 0;
    for (int index = start_index; index < argc; ++index) {
        length += strlen(argv[index]) + 1;
    }

    char *buffer = (char *)calloc(length + 1, sizeof(char));
    if (buffer == NULL) {
        return NULL;
    }

    for (int index = start_index; index < argc; ++index) {
        strcat(buffer, argv[index]);
        if (index + 1 < argc) {
            strcat(buffer, " ");
        }
    }

    return buffer;
}

static char *read_stdin_all(void) {
    size_t capacity = 1024;
    size_t length = 0;
    char *buffer = (char *)malloc(capacity);
    if (buffer == NULL) {
        return NULL;
    }

    int ch;
    while ((ch = fgetc(stdin)) != EOF) {
        if (length + 1 >= capacity) {
            capacity *= 2;
            char *grown = (char *)realloc(buffer, capacity);
            if (grown == NULL) {
                free(buffer);
                return NULL;
            }
            buffer = grown;
        }
        buffer[length++] = (char)ch;
    }
    buffer[length] = '\0';
    return buffer;
}

int main(int argc, char **argv) {
    char *input = NULL;

    if (argc > 1) {
        input = join_arguments(argc, argv, 1);
    }

    if (input == NULL) {
        input = read_stdin_all();
    }

    if (input == NULL || input[0] == '\0') {
        fprintf(stderr, "Usage: mathlang_solver \"sum of 5 and 3\"\n");
        free(input);
        return 1;
    }

    SolverResult result;
    solver_result_init(&result);

    if (!solve_expression(input, &result)) {
        char *json = solver_result_to_json(&result);
        if (json != NULL) {
            puts(json);
            free(json);
        }
        solver_result_free(&result);
        free(input);
        return 0;
    }

    char *json = solver_result_to_json(&result);
    if (json != NULL) {
        puts(json);
        free(json);
    }

    solver_result_free(&result);
    free(input);
    return 0;
}
