<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Discount Master — Demo</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <style>
    body { background: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    .dm-badge {
      display: inline-flex;
      align-items: center;
      padding: 3px 10px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
      line-height: 1.4;
      white-space: nowrap;
    }
    .dm-badge.batchwise { background: #e0f2fe; color: #0369a1; }
    .dm-badge.productwise { background: #dcfce7; color: #15803d; }
    .dm-badge.pointwise { background: #f3e8ff; color: #7e22ce; }
    .dm-badge.amount_wise { background: #ffedd5; color: #c2410c; }
    .dm-badge.schemewise { background: #fee2e2; color: #b91c1c; }
    .dm-pill {
      display: inline-flex;
      align-items: center;
      padding: 3px 10px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
    }
    .dm-pill.active { background: #dcfce7; color: #15803d; }
    .dm-pill.inactive { background: #f1f5f9; color: #64748b; }
    .dm-section-title {
      margin: 0 0 10px;
      color: #1f2937;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }
    .dm-grid-8 {
      display: grid;
      grid-template-columns: repeat(8, minmax(0, 1fr));
      gap: 8px;
    }
    .dm-grid-8 > div {
      background: #f8fafc;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 8px;
      text-align: center;
    }
    .dm-grid-8 .dm-label {
      font-size: 10px;
      color: #64748b;
      font-weight: 700;
      margin-bottom: 4px;
    }
    .dm-grid-8 .dm-value {
      font-size: 12px;
      color: #111827;
      font-weight: 700;
    }
    .dm-mock-table td, .dm-mock-table th { vertical-align: middle; }
    .top-bar {
      background: #fff;
      border-bottom: 1px solid #e2e5ec;
      padding: 12px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .top-bar h1 {
      font-size: 18px;
      margin: 0;
      font-weight: 700;
    }
    .container-main {
      max-width: 1400px;
      margin: 0 auto;
      padding: 24px;
    }
  </style>
</head>
<body>

  <div class="top-bar">
    <h1>Discount Master <span style="font-weight:400;color:#64748b;font-size:14px;">— Demo Implementation</span></h1>
    <button type="button" class="btn btn-sm btn-primary">Add New Discount</button>
  </div>

  <div class="container-main">
    <div class="card mb-3">
      <div class="card-header">
        <p class="text-muted mb-0 mt-1" style="font-size:12px;">
          Centralized discount configuration. Select Discount Type and Applied On to reveal relevant fields.
        </p>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-lg-4">
            <input type="text" class="form-control form-control-sm" placeholder="Search by Discount ID, Product, SKU, Batch">
          </div>
          <div class="col-lg-2">
            <select class="form-control form-control-sm">
              <option value="">All Types</option>
              <option>Batchwise</option>
              <option>Productwise</option>
              <option>Pointwise</option>
              <option>Amount wise</option>
              <option>Schemewise</option>
            </select>
          </div>
          <div class="col-lg-2">
            <select class="form-control form-control-sm">
              <option value="">All Status</option>
              <option>Active</option>
              <option>Inactive</option>
            </select>
          </div>
          <div class="col-lg-4">
            <input type="text" class="form-control form-control-sm" placeholder="Date range">
          </div>
        </div>

        <div class="table-responsive dm-mock-table">
          <table class="table table-bordered mb-0">
            <thead>
              <tr>
                <th style="width:40px;"><input type="checkbox"></th>
                <th>Discount ID</th>
                <th>Type</th>
                <th>Applied On</th>
                <th>Product / SKU / Batch</th>
                <th>Base Rate</th>
                <th>Discount Value</th>
                <th>Effective Rate</th>
                <th>Status</th>
                <th>From Date</th>
                <th>To Date</th>
                <th style="width:140px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><input type="checkbox"></td>
                <td><strong>D-BW-001</strong></td>
                <td><span class="dm-badge batchwise">Batchwise</span></td>
                <td>Same as Batch/Lot.No</td>
                <td>
                  <div>Product A</div>
                  <div style="font-size:11px;color:#64748b;">Batch: B-2024-001</div>
                </td>
                <td>₹6,000</td>
                <td>5% (Flat ₹300)</td>
                <td>₹5,700</td>
                <td><span class="dm-pill active">Active</span></td>
                <td>01-09-2024</td>
                <td>31-03-2025</td>
                <td>
                  <button class="btn btn-sm btn-outline-primary">Edit</button>
                  <button class="btn btn-sm btn-outline-danger">Delete</button>
                </td>
              </tr>
              <tr>
                <td><input type="checkbox"></td>
                <td><strong>D-PW-001</strong></td>
                <td><span class="dm-badge productwise">Productwise</span></td>
                <td>Full Variant</td>
                <td>
                  <div>Product B</div>
                  <div style="font-size:11px;color:#64748b;">Variant: Regular-Standard-SS-India</div>
                </td>
                <td>₹10,000</td>
                <td>7.689%</td>
                <td>₹9,231</td>
                <td><span class="dm-pill active">Active</span></td>
                <td>01-08-2024</td>
                <td>—</td>
                <td>
                  <button class="btn btn-sm btn-outline-primary">Edit</button>
                  <button class="btn btn-sm btn-outline-danger">Delete</button>
                </td>
              </tr>
              <tr>
                <td><input type="checkbox"></td>
                <td><strong>D-POW-01</strong></td>
                <td><span class="dm-badge pointwise">Pointwise Earn</span></td>
                <td>SKU Product</td>
                <td>
                  <div>Product C</div>
                  <div style="font-size:11px;color:#64748b;">SKU: DPI-T1</div>
                </td>
                <td>₹2,000</td>
                <td>200 pts / ₹100</td>
                <td>₹2,000</td>
                <td><span class="dm-pill active">Active</span></td>
                <td>01-07-2024</td>
                <td>—</td>
                <td>
                  <button class="btn btn-sm btn-outline-primary">Edit</button>
                  <button class="btn btn-sm btn-outline-danger">Delete</button>
                </td>
              </tr>
              <tr>
                <td><input type="checkbox"></td>
                <td><strong>D-AW-001</strong></td>
                <td><span class="dm-badge amount_wise">Amount wise</span></td>
                <td>Full Variant</td>
                <td>
                  <div>Product D</div>
                  <div style="font-size:11px;color:#64748b;">Variant: T Handle</div>
                </td>
                <td>₹50,000</td>
                <td>Flat ₹650 (1.3%)</td>
                <td>₹49,350</td>
                <td><span class="dm-pill inactive">Inactive</span></td>
                <td>01-06-2024</td>
                <td>30-06-2024</td>
                <td>
                  <button class="btn btn-sm btn-outline-primary">Edit</button>
                  <button class="btn btn-sm btn-outline-danger">Delete</button>
                </td>
              </tr>
              <tr>
                <td><input type="checkbox"></td>
                <td><strong>D-SW-001</strong></td>
                <td><span class="dm-badge schemewise">Schemewise</span></td>
                <td>SKU Product</td>
                <td>
                  <div>Product E</div>
                  <div style="font-size:11px;color:#64748b;">Scheme: Free Qty 5 / Scheme % 10%</div>
                </td>
                <td>₹500</td>
                <td>Free Qty: 5</td>
                <td>₹4,545</td>
                <td><span class="dm-pill active">Active</span></td>
                <td>01-09-2024</td>
                <td>—</td>
                <td>
                  <button class="btn btn-sm btn-outline-primary">Edit</button>
                  <button class="btn btn-sm btn-outline-danger">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-3 d-flex justify-content-between align-items-center">
          <div>
            <button class="btn btn-sm btn-outline-success">Bulk Activate</button>
            <button class="btn btn-sm btn-outline-warning">Bulk Deactivate</button>
            <button class="btn btn-sm btn-outline-danger">Bulk Delete</button>
          </div>
          <nav>
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item disabled"><a class="page-link" href="#">«</a></li>
              <li class="page-item active"><a class="page-link" href="#">1</a></li>
              <li class="page-item"><a class="page-link" href="#">2</a></li>
              <li class="page-item"><a class="page-link" href="#">3</a></li>
              <li class="page-item"><a class="page-link" href="#">»</a></li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0 h6">Add New Discount — Static Preview</h5>
        <p class="text-muted mb-0 mt-1" style="font-size:11px;">
          This is a static representation of the dynamic form. All sections are shown for review.
        </p>
      </div>
      <div class="card-body">
        <form>
          <div class="row">
            <div class="col-lg-4">
              <div class="form-group">
                <label>Discount ID</label>
                <input type="text" class="form-control form-control-sm" value="D-BW-001" readonly>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="form-group">
                <label>Discount Type</label>
                <select class="form-control form-control-sm">
                  <option>Batchwise Discount</option>
                  <option>Productwise Discount</option>
                  <option>Pointwise Earn</option>
                  <option>Amount wise Discount</option>
                  <option>Schemewise Discount</option>
                </select>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="form-group">
                <label>Applied On</label>
                <select class="form-control form-control-sm">
                  <option>SKU Product</option>
                  <option>Full Variant</option>
                  <option selected>Same as Batch/Lot.No</option>
                </select>
              </div>
            </div>
          </div>

          <div class="row mt-2">
            <div class="col-lg-4">
              <div class="form-group">
                <label>Product / SKU / Batch</label>
                <select class="form-control form-control-sm">
                  <option>Product A — Batch: B-2024-001</option>
                </select>
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label>Stock Available</label>
                <input type="text" class="form-control form-control-sm" value="500" readonly>
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label>Mfg. Date</label>
                <input type="text" class="form-control form-control-sm" value="11-09-2024" readonly>
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label>Expiry Date</label>
                <input type="text" class="form-control form-control-sm" value="11-09-2026" readonly>
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label>COA Document</label>
                <input type="text" class="form-control form-control-sm" value="coa_b001.pdf" readonly>
              </div>
            </div>
          </div>

          <div class="row mt-2">
            <div class="col-lg-12">
              <p class="dm-section-title">Stock Value As Per (Role-Wise Price Grid)</p>
              <div class="dm-grid-8">
                <div>
                  <div class="dm-label">PTS</div>
                  <div class="dm-value">₹5,000</div>
                </div>
                <div>
                  <div class="dm-label">PTR</div>
                  <div class="dm-value" style="background:#e0f2fe;">₹6,000</div>
                </div>
                <div>
                  <div class="dm-label">PTD</div>
                  <div class="dm-value">₹7,000</div>
                </div>
                <div>
                  <div class="dm-label">Govt.</div>
                  <div class="dm-value">₹9,000</div>
                </div>
                <div>
                  <div class="dm-label">Export</div>
                  <div class="dm-value">₹10,000</div>
                </div>
                <div>
                  <div class="dm-label">Customers(B2C)</div>
                  <div class="dm-value">₹9,500</div>
                </div>
                <div>
                  <div class="dm-label">M.R.P</div>
                  <div class="dm-value">₹20,000</div>
                </div>
                <div>
                  <div class="dm-label">Type</div>
                  <div class="dm-value">Flat</div>
                </div>
              </div>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-lg-2">
              <div class="form-group">
                <label>Qty Slab From</label>
                <input type="number" class="form-control form-control-sm" value="1">
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label>Qty Slab To</label>
                <input type="number" class="form-control form-control-sm" placeholder="Unlimited">
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label>Rate (Base)</label>
                <input type="text" class="form-control form-control-sm" value="₹6,000">
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label>Amount</label>
                <input type="text" class="form-control form-control-sm" value="₹6,000">
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label>Effective Rate</label>
                <input type="text" class="form-control form-control-sm" value="₹5,700">
              </div>
            </div>
            <div class="col-lg-2">
              <div class="form-group">
                <label>Discount %</label>
                <input type="text" class="form-control form-control-sm" value="5%">
              </div>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-lg-3">
              <div class="form-group">
                <label>Discount Type</label>
                <select class="form-control form-control-sm">
                  <option>Amount Or %</option>
                  <option>% Or Amount</option>
                </select>
              </div>
            </div>
            <div class="col-lg-3">
              <div class="form-group">
                <label>Amount</label>
                <input type="text" class="form-control form-control-sm" value="300">
              </div>
            </div>
            <div class="col-lg-3">
              <div class="form-group">
                <label>%</label>
                <input type="text" class="form-control form-control-sm" value="5">
              </div>
            </div>
            <div class="col-lg-3">
              <div class="form-group">
                <label>Total Discount</label>
                <input type="text" class="form-control form-control-sm" value="₹300">
              </div>
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-lg-3">
              <div class="form-group">
                <label>From Date</label>
                <input type="text" class="form-control form-control-sm" value="01-09-2024">
              </div>
            </div>
            <div class="col-lg-3">
              <div class="form-group">
                <label>To Date</label>
                <input type="text" class="form-control form-control-sm" value="31-03-2025">
              </div>
            </div>
            <div class="col-lg-3">
              <div class="form-group">
                <label>Status</label>
                <select class="form-control form-control-sm">
                  <option selected>Active</option>
                  <option>Inactive</option>
                </select>
              </div>
            </div>
            <div class="col-lg-3">
              <div class="form-group">
                <label>Date Of Add / Edit</label>
                <input type="text" class="form-control form-control-sm" value="2024-09-01 10:30:00" readonly>
              </div>
            </div>
          </div>

          <div class="mt-3">
            <button type="button" class="btn btn-sm btn-primary">Save</button>
            <button type="button" class="btn btn-sm btn-secondary">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

</body>
</html>
