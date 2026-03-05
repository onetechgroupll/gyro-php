<?php
/**
 * Generic conversion interface
 * 
 * @author Gerd Riesselmann
 * @ingroup Interfaces
 */
interface IConverter {
	public function encode(mixed $value, mixed $params = false): mixed;
	public function decode(mixed $value, mixed $params = false): mixed;
}
?>