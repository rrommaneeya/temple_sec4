(() => {
    const event = document.getElementById('event_id');
    const party = document.getElementById('party_id');
    const options = Array.from(party.options).slice(1);
    const filter = () => {
        const selected = party.value;
        party.replaceChildren(new Option('ไม่ระบุผู้บริจาค', ''));
        for (const option of options) {
            if (option.dataset.event === event.value) party.add(option);
        }
        party.value = Array.from(party.options).some(option => option.value === selected) ? selected : '';
    };
    event.addEventListener('change', filter);
    filter();
})();
