<?php
header("Content-type: text/css; charset: utf-8");
?>
* {
margin: 0;
padding: 0;
box-sizing: border-box;
font-family: 'Open Sans', sans-serif;
}

body {
background-color: #f8fafc;
color: #1e293b;
padding-bottom: 60px;
}

/* Layout Grid Kolom Utama */
.main-layout {
max-width: 1100px;
margin: 40px auto;
padding: 0 20px;
display: grid;
grid-template-columns: 7fr 4fr;
gap: 30px;
align-items: start;
}

/* Desain Blok / Box Background Terpisah */
.block-card {
background: white;
border: 1px solid #e2e8f0;
padding: 24px;
border-radius: 12px;
box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
margin-bottom: 25px;
}

/* Detail Gambar Utama di Atas Deskripsi */
.detail-img {
width: 100%;
height: 450px;
object-fit: cover;
background: #cbd5e1;
border-radius: 8px;
display: block;
}

.location-tag {
display: flex;
align-items: center;
gap: 6px;
font-size: 0.85rem;
font-weight: 600;
color: #64748b;
text-transform: uppercase;
margin-bottom: 12px;
}

.hotel-name {
font-size: 2rem;
font-weight: 700;
color: #0f172a;
margin-bottom: 15px;
line-height: 1.3;
}

.hotel-desc {
font-size: 1rem;
color: #475569;
line-height: 1.7;
}

/* Form Pemesanan Sisi Kanan */
.form-select {
width: 100%;
padding: 12px;
font-size: 0.95rem;
border: 1px solid #cbd5e1;
border-radius: 6px;
outline: none;
background-color: #ffffff;
color: #0f172a;
font-weight: 600;
cursor: pointer;
}

.form-select:focus {
border-color: #dc2626;
}

.btn-booking {
display: block;
width: 100%;
text-align: center;
background: #dc2626;
color: white;
padding: 14px;
border: none;
font-weight: 600;
font-size: 1rem;
border-radius: 8px;
cursor: pointer;
transition: background 0.15s;
}

.btn-booking:hover {
background: #b91c1c;
}

.room-sold-out {
text-align: center;
color: #dc2626;
background: #fff5f5;
border: 1px solid #fed7d7;
padding: 14px;
border-radius: 8px;
font-weight: 600;
}

/* Fasilitas Section di bawah select pilihan */
.fasilitas-section {
background: #f8fafc;
border: 1px solid #e2e8f0;
padding: 15px;
border-radius: 8px;
}

.fasilitas-title {
font-size: 0.85rem;
font-weight: 700;
color: #0f172a;
margin-bottom: 10px;
text-transform: uppercase;
}

.fasilitas-list {
display: grid;
grid-template-columns: repeat(2, 1fr);
gap: 8px;
}

.fasilitas-item {
display: flex;
align-items: center;
gap: 6px;
font-size: 0.88rem;
color: #475569;
}

/* Card rekomendasi */
.rekomendasi-section {
max-width: 1100px;
margin: 50px auto;
padding: 0 20px;
}

.rekomendasi-title {
font-size: 1.5rem;
font-weight: 600;
margin-bottom: 25px;
color: #0f172a;
}

.rekomendasi-grid {
display: grid;
grid-template-columns: repeat(4, 1fr);
gap: 20px;
width: 100%;
}

.card-link {
text-decoration: none;
color: inherit;
display: flex;
flex-direction: column;
}

.hotel-card {
background: white;
border: 1px solid #e2e8f0;
border-radius: 8px;
width: 100%;
height: 100%;
display: flex;
flex-direction: column;
overflow: hidden;
box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.rating-stars {
    display: flex;
    gap: 2px;
    align-items: center;
}

.star-icon {
    width: 16px;
    height: 16px;
    fill: #f97316;
    filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.05));
}

.img-wrapper {
width: 100%;
height: 180px;
overflow: hidden;
}

.hotel-card-img {
width: 100%;
height: 100%;
object-fit: cover;
}

.card-body {
padding: 20px;
min-height: 220px;
flex-grow: 1;
display: flex;
flex-direction: column;
}

.card-meta {
display: flex;
align-items: center;
gap: 6px;
font-size: 0.75rem;
color: #64748b;
font-weight: 500;
margin-bottom: 8px;
}

.card-title {
font-size: 1.1rem;
font-weight: 600;
color: #0f172a;
margin-bottom: 10px;
}

.card-text {
color: #475569;
font-size: 0.88rem;
line-height: 1.5;
margin-bottom: 15px;
}

.price-wrapper {
margin-top: auto;
display: flex;
flex-direction: column;
padding-top: 8px;
}

.price-amount {
font-size: 1.1rem;
font-weight: 700;
color: #dc2626;
}

.price-row {
display: flex;
align-items: center;
gap: 6px;
}

.price-suffix {
font-size: 0.8rem;
font-weight: 400;
color: #686868;
}

.price-original {
font-size: 0.8rem;
color: #9ca3af;
text-decoration: line-through;
}

.discount-badge {
background: #fee2e2;
color: #991b1b;
font-size: 0.65rem;
font-weight: 700;
padding: 2px 5px;
border-radius: 3px;
}

/* Responsivitas */
@media (max-width: 1200px) { .rekomendasi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 950px) { .main-layout { grid-template-columns: 1fr; } }
@media (max-width: 768px) { .rekomendasi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .rekomendasi-grid { grid-template-columns: 1fr; } }