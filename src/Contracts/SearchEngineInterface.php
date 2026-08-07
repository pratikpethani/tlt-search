<?php

namespace TLTSuite\TLTSearch\Contracts;

interface SearchEngineInterface {

	public function create_index(): void;

	public function delete_index(): void;

	public function index_document( array $document ): void;

	public function delete_document( string $document_id ): void;

	public function search( string $query, array $options = [] ): array;
}