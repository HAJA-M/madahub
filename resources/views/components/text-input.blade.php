@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-brand-blue-500 dark:focus:border-brand-blue-700 focus:ring-brand-blue-500 dark:focus:ring-brand-blue-700 rounded-md shadow-sm']) }}>
