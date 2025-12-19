<x-filament-panels::page>
    <div class="space-y-6">
        {{-- So'rovnoma umumiy ma'lumotlari --}}
        <x-filament::section>
            <x-slot name="heading">
                Umumiy ma'lumot
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Jami ovozlar</div>
                    <div class="text-2xl font-bold text-primary-600">{{ $record->total_votes }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Ishtirokchilar</div>
                    <div class="text-2xl font-bold text-success-600">{{ $record->participants->count() }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Nomzodlar</div>
                    <div class="text-2xl font-bold text-info-600">{{ $record->candidates->count() }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Holat</div>
                    <div class="text-2xl font-bold {{ $record->isActive() ? 'text-success-600' : 'text-gray-400' }}">
                        {{ $record->isActive() ? 'Faol' : 'Tugagan' }}
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Nomzodlar va ovozlar --}}
        <x-filament::section>
            <x-slot name="heading">
                Nomzodlar va ovozlar
            </x-slot>

            <div class="space-y-4">
                @foreach($record->candidates->sortByDesc('vote_count') as $candidate)
                    <div class="border dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                @if($candidate->photo)
                                    <img src="{{ Storage::url($candidate->photo) }}"
                                         alt="{{ $candidate->name }}"
                                         class="w-12 h-12 rounded-full object-cover">
                                @endif
                                <div>
                                    <h3 class="font-semibold text-lg">{{ $candidate->name }}</h3>
                                    @if($candidate->description)
                                        <p class="text-sm text-gray-500">{{ $candidate->description }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-primary-600">{{ $candidate->vote_count }}</div>
                                <div class="text-sm text-gray-500">ovoz</div>
                            </div>
                        </div>

                        {{-- Progress bar --}}
                        <div class="mt-3">
                            <div class="flex justify-between text-sm mb-1">
                                <span>{{ number_format($candidate->vote_percentage, 2) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                <div class="bg-primary-600 h-2.5 rounded-full"
                                     style="width: {{ $candidate->vote_percentage }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- So'nggi ovozlar --}}
        <x-filament::section>
            <x-slot name="heading">
                So'nggi ovozlar
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 text-left">Foydalanuvchi</th>
                            <th class="px-4 py-2 text-left">Nomzod</th>
                            <th class="px-4 py-2 text-left">Telefon</th>
                            <th class="px-4 py-2 text-left">IP manzil</th>
                            <th class="px-4 py-2 text-left">Vaqt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @foreach($record->votes()->with(['participant', 'candidate'])->latest()->limit(20)->get() as $vote)
                            <tr>
                                <td class="px-4 py-2">
                                    {{ $vote->participant->full_name }}
                                    @if($vote->participant->username)
                                        <span class="text-gray-500">(@{{ $vote->participant->username }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">{{ $vote->candidate->name }}</td>
                                <td class="px-4 py-2">{{ $vote->participant->phone ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $vote->ip_address ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $vote->voted_at->format('d.m.Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
