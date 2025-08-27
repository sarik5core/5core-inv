<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stands Quotation Form</title>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #f72585;
            --light-color: #f8f9fa;
            --medium-light: #e9ecef;
            --medium-gray: #adb5bd;
            --dark-gray: #495057;
            --success-color: #4cc9f0;
            --border-radius: 6px;
            --box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--dark-gray);
        }

        .form-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border-top: 5px solid var(--primary-color);
        }

        .row-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .image-container {
            flex: 0 0 auto;
        }

        .logo {
            height: 75px;
        }

        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }


        .image {
            height: 220px;
        }

        .form-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
            text-align: left;
            flex: 1;
        }

        @media (max-width: 768px) {
            .row-header {
                flex-direction: column;
                align-items: center;
            }

            .form-title {
                width: 100%;
                margin-top: 10px;
            }

            .image {
                max-width: 100%;
                height: auto;
            }
        }

        h1 {
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 25px;
            font-size: 2rem;
            font-weight: 600;
            letter-spacing: -0.5px;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
            min-width: calc(50% - 20px);
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        input[type="text"],
        input[type="number"],
        input[type="url"],
        select,
        textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--medium-light);
            border-radius: var(--border-radius);
            box-sizing: border-box;
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: var(--light-color);
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            background-color: white;
        }

        .file-upload-group {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .file-preview {
            max-width: 100px;
            max-height: 100px;
            display: none;
        }

        .add-file-btn {
            background-color: var(--light-color);
            border: 1px dashed var(--medium-gray);
            color: var(--primary-color);
            cursor: pointer;
            font-size: 0.85rem;
            margin-top: 8px;
            padding: 8px 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .add-file-btn:hover {
            background-color: rgba(67, 97, 238, 0.05);
            border-color: var(--primary-color);
        }

        .add-file-btn::before {
            content: "+";
            font-weight: bold;
            font-size: 1.1rem;
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 1rem;
            width: 200px;
            display: block;
            margin: 30px auto 0;
            transition: var(--transition);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .submit-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .full-width {
            flex: 0 0 100%;
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .required::after {
            content: " *";
            color: var(--accent-color);
        }

        .range-inputs {
            display: flex;
            gap: 10px;
        }

        .range-inputs div {
            flex: 1;
        }

        .section-title {
            font-size: 1.1rem;
            color: var(--primary-color);
            margin: 25px 0 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--medium-light);
            font-weight: 600;
        }

        .details-row {
            display: flex;
            gap: 20px;
        }

        .details-row .form-group {
            flex: 1;
        }

        @media (max-width: 768px) {

            .form-group,
            .details-row .form-group {
                min-width: 100%;
            }

            .form-container {
                padding: 20px;
            }

            .range-inputs {
                flex-direction: column;
                gap: 10px;
            }
        }

        /* Loading spinner */
        .spinner {
            display: none;
            width: 40px;
            height: 40px;
            margin: 0 auto;
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .status-message {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            border-radius: var(--border-radius);
            display: none;
        }

        .success {
            background-color: rgba(76, 201, 240, 0.2);
            color: var(--dark-gray);
            border: 1px solid var(--success-color);
        }

        .error {
            background-color: rgba(247, 37, 133, 0.2);
            color: var(--dark-gray);
            border: 1px solid var(--accent-color);
        }

        .title-block {
            flex: 1;
            text-align: left;
        }

        .form-subtitle {
            font-size: 1rem;
            color: var(--dark-gray);
            margin-top: 6px;
            margin-bottom: 0;
        }

        /* For 3-column layout in specific sections */
        .three-column-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .three-column-row .form-group {
            flex: 1;
            min-width: calc(33.33% - 14px);
            /* accounting for gap */
        }

        @media (max-width: 768px) {
            .three-column-row .form-group {
                min-width: 100%;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>
    <div class="form-container">
        <div class="logo-container">
            <img src="{{ asset('images/5core_logo.png') }}" alt="5core Logo" class="logo">
        </div>
        <div class="content-box">
            <div class="row-header">
                <div class="title-block">
                    <h1 class="form-title">📌 {{$rfqForm->title}}</h1>
                    <p class="form-subtitle">{{ $rfqForm->subtitle }}</p>
                </div>
                <div class="image-container">
                    <img src="{{ asset('storage/' . $rfqForm->main_image) }}" alt="stand" class="image">
                </div>
            </div>
            <form id="productForm" action="submit.php" method="POST" enctype="multipart/form-data">
                <div class="section-title">Supplier Details (供应商详情)</div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="supplierName" class="required">Supplier Name</label>
                        <input type="text" id="supplierName" name="supplierName" required>
                    </div>
                    <div class="form-group">
                        <label for="companyName" class="required">Company Name</label>
                        <input type="text" id="companyName" name="companyName" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="supplierLink">Supplier Link</label>
                        <input type="url" id="supplierLink" name="supplierLink" placeholder="https://">
                    </div>
                    <div class="form-group">
                        <label for="productName" class="required">Product Name</label>
                        <input type="text" id="productName" name="productName" required>
                    </div>
                </div>



                <!-- Product Details Section -->
                <div class="section-title">Product Specifications(产品规格)</div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mainProductImage" class="required">Your Product Image</label>
                        <input type="file" id="mainProductImage" name="mainProductImage" accept="image/*"
                            class="file-input" required>
                        <img class="file-preview" src="#" alt="Preview">
                    </div>
                    @if ($rfqForm->category && ($rfqForm->category->name === "STAND" || $rfqForm->category->name === "Stand"))
                        <div class="form-group">
                            <label for="material" class="required">Material</label>
                            <select id="material" name="material" required>
                                <option value="">Select</option>
                                <option value="Alimunium">Alimunium</option>
                                <option value="Steel">Steel</option>
                            </select>
                        </div>
                    @elseif ($rfqForm->category && $rfqForm->category->name === "Wired Microphone")
                        <div class="form-group">
                            <label for="material" class="required">Pollar Pattern</label>
                            <select id="PollarPattern" name="pollar_pattern" required="">
                                <option value="">Select</option>
                                <option value="Cardioid">Cardioid</option>
                                <option value="Omnidirectional">Omnidirectional</option>
                                <option value="Bidirectional">Bidirectional</option>
                                <option value="Supercardioid">Supercardioid</option>
                                <option value="Hypercardioid">Hypercardioid</option>
                            </select>
                        </div>
                    @elseif ($rfqForm->category && $rfqForm->category->name === "Wireless Microphone")
                        <div class="form-group">
                            <label for="material" class="required">Pollar Pattern</label>
                           <select id="MicrophoneType" name="MicrophoneType" required="">
                                <option value="">Select</option>
                                <option value="Dynamic">Dynamic</option>
                                <option value="Condensor">Condensor</option>
                            </select>
                        </div>
                    @else
                        <div class="form-group">
                            <label for="material" class="required">Material</label>
                            <input type="text" id="Load_Capacity" name="Load_Capacity" required>
                        </div>
                    @endif
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="NW" class="required">NW(KG)</label>
                        <input type="number" id="NW" name="NW" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="GW_KG" class="required">GW(KG)</label>
                        <input type="number" id="GW_KG" name="GW_KG" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="Load_Capacity" class="required">Load Capacity</label>
                        <input type="text" id="Load_Capacity" name="Load_Capacity" required>
                    </div>

                    <div class="form-group">
                        <div class="range-inputs">
                            <div>
                                <label for="heightMin" class="required">Height Min (cm)</label>
                                <input type="number" id="heightMin" name="heightMin" step="0.01" min="0"
                                    placeholder="Min" required>
                            </div>
                            <div>
                                <label for="heightMax" class="required">Height Max (cm)</label>
                                <input type="number" id="heightMax" name="heightMax" step="0.01" min="0"
                                    placeholder="Max" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="lockingMechanism" class="required">Locking Mechanism</label>
                        <input type="text" id="lockingMechanism" name="lockingMechanism" required>
                        <label for="lockingMechanismImage" class="required">Add Image</label>
                        <input type="file" id="lockingMechanismImage" name="lockingMechanismImage" accept="image/*" class="file-input" required>
                        <img class="file-preview" src="#" alt="Preview">
                    </div>

                    <div class="form-group">
                        <label for="colorOption" class="required">Color Option Available?</label>
                        <select id="colorOption" name="colorOption" required>
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="packingType" class="required">Packing Inner</label>
                        <select id="packingType" name="packingType" required>
                            <option value="">Select</option>
                            <option value="Gift Box">Gift Box</option>
                            <option value="Brown Box">Brown Box</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="Packing_GSM" class="required">Packing GSM</label>
                        <input type="number" id="Packing_GSM" name="Packing_GSM" step="0" min="0"
                            placeholder="0" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <div class="range-inputs">
                            <span class="dimension-label">Dimension Inner Box - </span>
                            <div>
                                <label for="Length" class="required">Length (cm)</label>
                                <input type="number" id="Length" name="Length" step="0.01" min="0"
                                    placeholder="Length" required>
                            </div>
                            <div>
                                <label for="Width" class="required">Width (cm)</label>
                                <input type="number" id="Width" name="Width" step="0.01" min="0"
                                    placeholder="Width" required>
                            </div>
                            <div>
                                <label for="Height" class="required">Height (cm)</label>
                                <input type="number" id="Height" name="Height" step="0.01" min="0"
                                    placeholder="Height" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Dimension -->
                <div class="form-row">
                    <div class="form-group">
                        <div class="range-inputs">
                            <span class="dimension-label">Product Dimension - </span>
                            <div>
                                <label for="productWidth" class="required">Width (W)</label>
                                <input type="number" id="productWidth" name="productWidth" step="0.01"
                                    min="0" placeholder="Width (cm)" required>
                            </div>
                            <div>
                                <label for="productDepth" class="required">Depth (D)</label>
                                <input type="number" id="productDepth" name="productDepth" step="0.01"
                                    min="0" placeholder="Depth (cm)" required>
                            </div>
                            <div>
                                <label for="productHeight" class="required">Height (H)</label>
                                <input type="number" id="productHeight" name="productHeight" step="0.01"
                                    min="0" placeholder="Height (cm)" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Package Dimension -->
                <div class="form-row">
                    <div class="form-group">
                        <div class="range-inputs">
                            <span class="dimension-label">Package Dimension - </span>
                            <div>
                                <label for="packageWidth" class="required">Width (W)</label>
                                <input type="number" id="packageWidth" name="packageWidth" step="0.01"
                                    min="0" placeholder="Width (cm)" required>
                            </div>
                            <div>
                                <label for="packageDepth" class="required">Depth (D)</label>
                                <input type="number" id="packageDepth" name="packageDepth" step="0.01"
                                    min="0" placeholder="Depth (cm)" required>
                            </div>
                            <div>
                                <label for="packageHeight" class="required">Height (H)</label>
                                <input type="number" id="packageHeight" name="packageHeight" step="0.01"
                                    min="0" placeholder="Height (cm)" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pricing Section -->
                <div class="section-title">Pricing & MOQ (价格和起订量)</div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="usdPrice" class="required">USD Price</label>
                        <input type="number" id="usdPrice" name="usdPrice" step="0" min="0"
                            placeholder="0" required>
                    </div>
                    <div class="form-group">
                        <label for="rmbPrice" class="required">RMB Price</label>
                        <input type="number" id="rmbPrice" name="rmbPrice" step="0.01" min="0"
                            placeholder="0.00" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <div class="form-group">
                            <label for="moq" class="required">MOQ</label>
                            <input type="number" id="moq" name="moq" step="0.01" min="0"
                                placeholder="0" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="priceType" class="required">Price Type</label>
                        <select id="priceType" name="priceType" required>
                            <option value="">Select</option>
                            <option value="FOB">FOB</option>
                            <option value="EXW">EXW</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cbm" class="required">CBM</label>
                        <input type="number" id="cbm" name="cbm" step="0.01" min="0"
                            placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                    </div>
                </div>

                <!-- Product Photos Section -->
                <div class="section-title">Product Images Additional (产品附加图片 (Chǎnpǐn fùjiā túpiàn))</div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <div id="fileUploadContainer">
                            <div class="file-upload-group">
                                <input type="file" name="additionalPhotos[]" accept="image/*" class="file-input"
                                    multiple>
                                <img class="file-preview" src="#" alt="Preview">
                            </div>
                        </div>
                        <button type="button" class="add-file-btn" id="addFileBtn">Add another photo</button>
                    </div>
                </div>


                <!-- Additional Information Section -->
                <div class="section-title">Additional Information (的中文翻译是)</div>
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="reviews">Add any other point as to why we should consider your product over
                            others.</label>
                        <textarea id="reviews" name="reviews"></textarea>
                    </div>
                </div>
                <button type="submit" class="submit-btn">Submit Product</button>
            </form>
        </div>
    </div>

</body>

</html>
