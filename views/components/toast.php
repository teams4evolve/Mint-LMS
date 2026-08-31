<?php
declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$id = $id ?? 'mint-toast-container';
?>
<div
	id="<?php echo esc_attr( $id ); ?>"
	x-data="mintToastContainer()"
	x-on:mint-toast.window="add($event.detail)"
	class="mint-fixed mint-bottom-4 mint-right-4 mint-z-[100] mint-flex mint-flex-col mint-gap-2 mint-pointer-events-none"
	aria-live="polite"
	aria-atomic="true"
>
	<template x-for="toast in toasts" :key="toast.id">
		<div
			x-show="toast.visible"
			x-transition:enter="mint-transition-all mint-duration-300"
			x-transition:enter-start="mint-opacity-0 mint-translate-y-2"
			x-transition:enter-end="mint-opacity-100 mint-translate-y-0"
			x-transition:leave="mint-transition-all mint-duration-200"
			x-transition:leave-start="mint-opacity-100 mint-translate-y-0"
			x-transition:leave-end="mint-opacity-0 mint-translate-y-2"
			class="mint-pointer-events-auto mint-flex mint-items-center mint-gap-3 mint-px-4 mint-py-3 mint-rounded-md mint-shadow-md mint-border mint-min-w-[16rem] mint-max-w-sm"
			:class="{
				'mint-bg-accent mint-text-neutral-50 mint-border-accent': toast.type === 'success',
				'mint-bg-neutral-800 mint-text-neutral-50 mint-border-neutral-800': toast.type === 'error',
				'mint-bg-neutral-50 mint-text-neutral-700 mint-border-neutral-200': toast.type === 'info' || toast.type === 'default'
			}"
			role="status"
		>
			<span class="mint-text-sm mint-flex-1" x-text="toast.message"></span>
			<button
				type="button"
				class="mint-shrink-0 mint-p-1 mint-rounded-sm hover:mint-opacity-75"
				@click="dismiss(toast.id)"
				aria-label="<?php echo esc_attr__( 'Dismiss', 'mint-lms' ); ?>"
			>
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
	</template>
</div>
