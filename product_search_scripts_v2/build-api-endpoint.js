function buildApiEndpoint(filterData) {
    const params = new URLSearchParams();

    // Loop through each category
    for (const [categoryName, filters] of Object.entries(filterData)) {
        for (const [key, value] of Object.entries(filters)) {
            if (Array.isArray(value)) {
                value.forEach(v => params.append(`${categoryName}[${key}][]`, v));
            } else if (typeof value === 'object' && value !== null) {
                if (value.min) params.append(`${categoryName}[${key}][min]`, value.min);
                if (value.max) params.append(`${categoryName}[${key}][max]`, value.max);
            } else {
                params.append(`${categoryName}[${key}]`, value);
            }
        }
    }

    return '/your-api-endpoint.php?' + params.toString();
}
