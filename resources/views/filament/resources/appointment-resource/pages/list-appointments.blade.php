<x-filament-panels::page>
    {{ $this->table }}
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function initializeGroups() {
                // Find the table Alpine component
                const tableComponent = document.querySelector('[x-data*="table"]');
                if (!tableComponent || !tableComponent._x_dataStack || !tableComponent._x_dataStack[0]) {
                    setTimeout(initializeGroups, 300);
                    return;
                }
                
                const tableData = tableComponent._x_dataStack[0];
                
                if (!tableData.toggleCollapseGroup || !tableData.isGroupCollapsed || !tableData.collapsedGroups) {
                    setTimeout(initializeGroups, 300);
                    return;
                }
                
                // Get all group headers
                const groupHeaders = Array.from(document.querySelectorAll('.fi-ta-group-header'));
                
                if (groupHeaders.length === 0) {
                    setTimeout(initializeGroups, 300);
                    return;
                }
                
                // Collect all group titles by reading from h4 text content
                const groupTitles = [];
                groupHeaders.forEach((header) => {
                    const titleElement = header.querySelector('h4');
                    if (titleElement) {
                        // Get the full text content and remove "Date:" prefix if present
                        let title = titleElement.textContent.trim();
                        title = title.replace(/^Date:\s*/i, '').trim();
                        if (title) {
                            groupTitles.push(title);
                        }
                    }
                });
                
                if (groupTitles.length === 0) {
                    setTimeout(initializeGroups, 300);
                    return;
                }
                
                // First, collapse all groups
                groupTitles.forEach((groupTitle) => {
                    if (!tableData.isGroupCollapsed(groupTitle)) {
                        tableData.toggleCollapseGroup(groupTitle);
                    }
                });
                
                // Then expand only the first group
                if (groupTitles.length > 0 && tableData.isGroupCollapsed(groupTitles[0])) {
                    tableData.toggleCollapseGroup(groupTitles[0]);
                }
            }
            
            // Wait for Alpine.js to initialize
            setTimeout(initializeGroups, 1000);
        });
    </script>
</x-filament-panels::page>

