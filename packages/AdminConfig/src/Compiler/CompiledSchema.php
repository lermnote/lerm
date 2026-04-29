<?php
/**
 * Immutable compiled schema payload.
 *
 * @package Lerm\AdminConfig
 */

declare( strict_types=1 );

namespace Lerm\AdminConfig\Compiler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CompiledSchema {

	/**
	 * @param array<string, mixed>               $definition
	 * @param array<string, mixed>               $defaults
	 * @param array<string, array<string, mixed>> $dependency_graph
	 * @param array<string, array<string, mixed>> $field_metadata
	 * @param array<string, mixed>               $client_config
	 * @param array<string, mixed>               $container
	 * @param array<string, mixed>               $store
	 */
	public function __construct(
		private string $id,
		private array $definition,
		private array $defaults,
		private array $dependency_graph,
		private array $field_metadata,
		private array $client_config,
		private array $container,
		private array $store
	) {
	}

	public function id(): string {
		return $this->id;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		return $this->definition;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function defaults(): array {
		return $this->defaults;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public function dependency_graph(): array {
		return $this->dependency_graph;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public function field_metadata(): array {
		return $this->field_metadata;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function client_config(): array {
		return $this->client_config;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function container(): array {
		return $this->container;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function store(): array {
		return $this->store;
	}
}
