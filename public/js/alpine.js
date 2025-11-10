document.addEventListener('alpine:init', () => {
  Alpine.data('quoteForm', (formData, itemsToDisplay, products) => ({
    form: {
      ...formData,
      items: itemsToDisplay.length > 0 ? itemsToDisplay.map(item => ({
        ...item,
        _id: Date.now() + Math.random()
      })) : [],
    },
    products: products,
    selectedProductId: '', // 初期値は空

    subtotal: 0,
    tax: 0,
    totalAmount: 0,

    init() {
      document.getElementById('customer_id').value = this.form.customer_id || '';
      document.getElementById('sales_rep_id').value = this.form.sales_rep_id || '';

      // 🌟 コピー明細がある場合、最初の明細を商品選択フォームに反映
      if (this.form.items.length > 0) {
        this.selectedProductId = this.form.items[0].item_id;
      }

      this.calculateTotals();
      this.setExpirationDate();
      this.updateCustomer();
      this.updateSalesRep();
    },

    updateCustomer() {
      const selectEl = document.getElementById('customer_id');
      const selectedOption = selectEl.options[selectEl.selectedIndex];
      this.form.customer_name = selectedOption.dataset.name || this.form.customer_name;
      this.form.customer_email = selectedOption.dataset.email || this.form.customer_email;
    },

    updateSalesRep() {
      const selectEl = document.getElementById('sales_rep_id');
      const selectedOption = selectEl.options[selectEl.selectedIndex];
      this.form.sales_rep_name = selectedOption.dataset.name || '';
    },

    setExpirationDate() {
      if (!this.form.issue_date) return;
      const issueDate = new Date(this.form.issue_date);
      issueDate.setDate(issueDate.getDate() + 30);
      this.form.expiration_date = issueDate.toISOString().split('T')[0];
    },
    
    setReceivedDate() {
      if (!this.form.received_date) return;
  const receivedDate = new Date(this.form.received_date);
  this.form.received_date = receivedDate.toISOString().split('T')[0];
    },
    addItem() {
      if (!this.selectedProductId) return;
      const product = this.products.find(p => p.id == this.selectedProductId);
      if (product) {
        this.form.items.push({
          _id: Date.now() + Math.random(),
          item_id: product.id,
          item_name: product.item_name,
          unit_price: parseFloat(product.unit_price) || 0,
          cost_type: '月額利用料',
          quantity: 1,
        });
        this.calculateTotals();
        this.selectedProductId = '';
      }
    },
    removeItem(index) {
      this.form.items.splice(index, 1);
      this.calculateTotals();
    },

    calculateTotals() {
      let subtotal = 0;
      this.form.items.forEach(item => {
        subtotal += (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
      });
      const taxRate = 0.10;
      const tax = Math.round(subtotal * taxRate * 100) / 100;
      this.subtotal = subtotal;
      this.tax = tax;
      this.totalAmount = subtotal + tax;
    },

    submitForm() {
      const formEl = this.$el; // this.$el は x-data が設定されている <form> 要素を指します

      // 既存の明細入力フィールドを一旦削除
      this.form.items.forEach((item, index) => {
        ['item_id','item_name','unit_price','cost_type','quantity'].forEach(field => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = `items[${index}][${field}]`;
          input.value = item[field] ?? '';
          formEl.appendChild(input);
        });
      });

      formEl.submit();
    }
  }));
});
