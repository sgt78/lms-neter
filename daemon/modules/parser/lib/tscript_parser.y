/*

T-Script - Parser
Copyright (C) 2004, Adrian Smarzewski <adrian@kadu.net>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

%{
	#include <stdlib.h>
	#include "tscript_ast.h"

	#define YYSTYPE tscript_ast_node*

	// http://lists.gnu.org/archive/html/bug-bison/2003-04/msg00045.html
	#define YYLEX_PARAM context

	int i;
%}

%error-verbose
%locations
%parse-param { tscript_context* context }
%lex-param { tscript_context* context }

%nonassoc ERROR IF ELSE END_IF FOR END_FOR WFILE END_WFILE
%left OR AND
%left EQUALS '<' '>' EQUALS_LESS EQUALS_GREATER DIFFERS
%left '!'
%left '+' '-'
%left NEG
%left '*' '/' '%' '&' '|'
%nonassoc MATCH
%nonassoc INC DEC
%nonassoc EXT CONST
%nonassoc LITERAL NUMBER TEXT NAME NULL_CONST TO_STRING TO_NUMBER TYPEOF

%%

template: 	commands
		{
			context->ast = $1;
		}

commands:	commands command
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_SEQ, $1, $2);
		}
	|	command
		{
			$$ = $1;
		}

command:	statement
	|	expression

statement:	set_stmt
	|	for_stmt
	|	if_stmt
	|	file_stmt

set_stmt:	reference '=' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_VAR_SET, $1, $3);
		}

for_stmt:	FOR '(' command ';' expression ';' command ')' commands END_FOR
		{
			$$ = tscript_ast_node_4(TSCRIPT_AST_FOR, $3, $5, $7, $9);
		}

if_stmt:	IF '(' expression ')' commands END_IF
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_IF, $3, $5);
		}
	|	IF '(' expression ')' commands ELSE commands END_IF
		{
			$$ = tscript_ast_node_3(TSCRIPT_AST_IF, $3, $5, $7);
		}

file_stmt:	WFILE expression commands END_WFILE
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_FILE, $2, $3);
		}

expressions:	expressions expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_SEQ, $1, $2);
		}
	|	expression
		{
			$$ = $1;
		}

expression:	TEXT
	|	LITERAL
	|	NUMBER
	|	NULL_CONST
		{
			$$ = tscript_ast_node_val(TSCRIPT_AST_VALUE,
				tscript_value_create_null());
		}
	|	CONST
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_CONST, $1);
		}
	|	EXT expressions '}'
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_EXT, $1, $2);
		}
	|	EXT '(' expression ')'
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_EXT, $1, $3);
		}
	|	'-' expression %prec NEG
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_NEG, $2);
		}
	|	'!' expression
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_NOT, $2);
		}
	|	'(' expression ')'
		{
			$$ = $2;
		}
	|	expression EQUALS expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_EQUALS, $1, $3);
		}
	|	expression DIFFERS expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_DIFFERS, $1, $3);
		}
	|	expression '<' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_LESS, $1, $3);
		}
	|	expression '>' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_GREATER, $1, $3);
		}
	|	expression EQUALS_LESS expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_EQUALS_LESS, $1, $3);
		}
	|	expression EQUALS_GREATER expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_EQUALS_GREATER, $1, $3);
		}
	|	expression OR expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_OR, $1, $3);
		}
	|	expression AND expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_AND, $1, $3);
		}
	|	expression '+' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_PLUS, $1, $3);
		}
	|	expression '-' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_MINUS, $1, $3);
		}
	|	expression '*' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_MUL, $1, $3);
		}
	|	expression '/' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_DIV, $1, $3);
		}
	|	expression '%' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_MOD, $1, $3);
		}
	|	expression '&' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_BAND, $1, $3);
		}
	|	expression '|' expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_BOR, $1, $3);
		}
	|	expression MATCH expression
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_MATCH, $1, $3);
		}
	|	reference INC
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_INC, $1);
		}
	|	reference DEC
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_DEC, $1);
		}
	|	expression '[' expression ']'
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_INDEX, $1, $3);
		}
	|	expression '.' NAME
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_SUBVAR, $1, $3);
		}
	|	reference
	|	type_conv
	|	TYPEOF '(' expression ')'
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_TYPEOF, $3);
		}

reference:	NAME
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_VAR_GET, $1);
		}
	|	reference '[' expression ']'
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_INDEX, $1, $3);
		}
	|	reference '.' NAME
		{
			$$ = tscript_ast_node_2(TSCRIPT_AST_SUBVAR, $1, $3);
		}

type_conv:	TO_STRING '(' expression ')'
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_CONV, $3);
			$$->value = tscript_value_create_null();
			$$->value->type = TSCRIPT_TYPE_STRING;
		}
	|	TO_NUMBER '(' expression ')'
		{
			$$ = tscript_ast_node_1(TSCRIPT_AST_CONV, $3);
			$$->value = tscript_value_create_null();
			$$->value->type = TSCRIPT_TYPE_NUMBER;
		}

%%
