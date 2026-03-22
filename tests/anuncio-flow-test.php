<?php
declare(strict_types=1);

// Bloqueia acesso direto via HTTP — só permite execução via CLI
if ( defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GCEP_ALLOW_STANDALONE_TESTS', true );

require_once __DIR__ . '/../includes/forms/class-anuncio-flow.php';

function assert_same( mixed $expected, mixed $actual, string $label ): void {
	if ( $expected !== $actual ) {
		fwrite(
			STDERR,
			sprintf(
				"[FAIL] %s\nEsperado: %s\nAtual: %s\n",
				$label,
				var_export( $expected, true ),
				var_export( $actual, true )
			)
		);
		exit( 1 );
	}

	fwrite( STDOUT, sprintf( "[OK] %s\n", $label ) );
}

$fallback   = 'Fallback de validação.';
$is_generic = static fn( string $reason ): bool => str_contains( strtolower( $reason ), 'fallback' ) || str_contains( strtolower( $reason ), 'corrija e tente novamente' );

$approved = GCEP_Anuncio_Flow::classify_ai_result(
	[
		'approved'      => true,
		'justificativa' => '',
		'error'         => '',
	],
	$is_generic,
	$fallback
);

assert_same( true, $approved['approved'], 'IA aprovada permanece aprovada' );
assert_same( false, $approved['has_blocking_rejection'], 'Aprovação não bloqueia' );

$blocking_rejection = GCEP_Anuncio_Flow::classify_ai_result(
	[
		'approved'      => false,
		'justificativa' => 'O título é genérico e a descrição não informa os serviços prestados.',
		'error'         => '',
	],
	$is_generic,
	$fallback
);

assert_same( true, $blocking_rejection['has_blocking_rejection'], 'Reprovação com motivo útil bloqueia' );
assert_same( false, $blocking_rejection['should_bypass_blocking'], 'Reprovação útil não entra em bypass' );

$generic_rejection = GCEP_Anuncio_Flow::classify_ai_result(
	[
		'approved'      => false,
		'justificativa' => '',
		'error'         => '',
	],
	$is_generic,
	$fallback
);

assert_same( false, $generic_rejection['has_blocking_rejection'], 'Reprovação genérica não bloqueia' );
assert_same( true, $generic_rejection['should_bypass_blocking'], 'Reprovação genérica entra em bypass' );

$ai_error = GCEP_Anuncio_Flow::classify_ai_result(
	[
		'approved'      => false,
		'justificativa' => '',
		'error'         => 'timeout',
	],
	$is_generic,
	$fallback
);

assert_same( true, $ai_error['should_bypass_blocking'], 'Erro técnico da IA não bloqueia o save' );

assert_same(
	'publicado',
	GCEP_Anuncio_Flow::determine_edit_success_status( 'publicado', 'pago', 'premium', false ),
	'Premium pago continua publicado após edição'
);

assert_same(
	'aguardando_pagamento',
	GCEP_Anuncio_Flow::determine_edit_success_status( 'rascunho', '', 'premium', true ),
	'Upgrade grátis para premium vai para aguardando pagamento'
);

assert_same(
	'expirado',
	GCEP_Anuncio_Flow::determine_edit_success_status( 'expirado', 'pago', 'premium', false ),
	'Premium expirado permanece expirado ao editar'
);

$create_free_approved = GCEP_Anuncio_Flow::determine_creation_outcome( 'gratis', true, $approved );
assert_same( 'publicado', $create_free_approved['status'], 'Criação grátis aprovada publica direto' );

$create_free_manual = GCEP_Anuncio_Flow::determine_creation_outcome( 'gratis', false, $generic_rejection );
assert_same( 'aguardando_aprovacao', $create_free_manual['status'], 'Criação grátis sem IA vai para aprovação manual' );

$create_premium_rejected = GCEP_Anuncio_Flow::determine_creation_outcome( 'premium', true, $blocking_rejection );
assert_same( 'rejeitado', $create_premium_rejected['status'], 'Criação premium rejeitada pela IA bloqueia antes do pagamento' );

$create_premium_payment = GCEP_Anuncio_Flow::determine_creation_outcome( 'premium', true, $approved );
assert_same( 'aguardando_pagamento', $create_premium_payment['status'], 'Criação premium aprovada vai para pagamento' );

fwrite( STDOUT, "\nTodos os testes de fluxo de anúncios passaram.\n" );
