<x-filament-panels::page>
    @php
        $table = $this->getTable();
    @endphp

    <div
        x-data="{
            init() {
                // Wait for table to be fully loaded
                this.$nextTick(() => {
                    // Get all group headers
                    const groupHeaders = document.querySelectorAll('.fi-ta-group-header');
                    
                    groupHeaders.forEach((header) => {
                        // Get the group title from the header
                        const titleElement = header.querySelector('h4');
                        if (titleElement) {
                            const title = titleElement.textContent.trim();
                            // Extract just the date part if there's a label prefix
                            const dateMatch = title.match(/Date:\s*(.+)/);
                            const groupTitle = dateMatch ? dateMatch[1] : title;
                            
                            // Collapse all groups by default
                            if (window.Alpine && window.Alpine.store('table')) {
                                const tableStore = window.Alpine.store('table');
                                if (tableStore && !tableStore.isGroupCollapsed(groupTitle)) {
                                    tableStore.toggleCollapseGroup(groupTitle);
                                }
                            }
                        }
                    });
                });
            }
        }"
    >
        {{ $this->table }}
    </div>
</x-filament-panels::page>

