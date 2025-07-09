/* Container cách top 100px */
.main-promotions-content {
  margin-top: 100px !important;
}

/* Sidebar Latest Articles */
.latest-articles-box {
  border: 1px solid #eee;
  background: #fff;
  font-size: 1rem;
  padding: 1rem 0.7rem 1rem 0.7rem !important;
}
.latest-articles-box h3 {
  font-size: 1.15rem;
  font-weight: bold;
  margin-bottom: 1rem;
}
.latest-article-item {
  border-bottom: 1px solid #f2f2f2;
  padding-bottom: 8px;
  margin-bottom: 8px;
}
.latest-article-item:last-child {
  border-bottom: none;
}
.latest-article-title {
  font-size: 0.7rem;
  line-height: 1.25;
  display: block;
  margin-bottom: 2px;
  font-weight: bold;
  text-transform: uppercase;
  color: #111;
  transition: color 0.2s;
  text-decoration: none;
}
.latest-article-title:hover {
  color: #007bff;
  text-decoration: underline;
}
.latest-article-item .text-muted {
  font-size: 0.7rem;
  margin-top: 2px;
}

/* Main Promotions Grid */
.promotion-card {
  border-radius: 14px;
  box-shadow: 0 2px 12px 0 rgba(0,0,0,0.08);
  background: #fff;
  font-size: 0.7rem;
  width: 100%;
  max-width: 370px;
  min-width: unset;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  border: none;
  padding: 0;
  transition: box-shadow 0.2s, transform 0.2s;
}
.promotion-card:hover {
  box-shadow: 0 8px 32px 0 rgba(0,0,0,0.13);
  transform: translateY(-4px) scale(1.01);
}
.promotion-img-fixed {
  width: 100%;
  height: 200px;
  object-fit: cover;
  margin: 0;
  display: block;
  border-top-left-radius: 14px;
  border-top-right-radius: 14px;
  border-bottom-left-radius: 0;
  border-bottom-right-radius: 0;
  background: #f8f8f8;
}
.promotion-card .card-body {
  padding: 0.7rem 10px 0.5rem 10px;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  box-sizing: border-box;
}
.promotion-title-fixed {
  font-size: 0.8rem !important;
  line-height: 1.3;
  font-weight: bold;
  text-align: center;
  margin-bottom: 0.5rem;
  margin-top: 0.2rem;
  text-transform: uppercase;
  word-break: break-word;
  white-space: normal;
  overflow: hidden;
  text-overflow: ellipsis;
  min-height: 36px;
  max-height: 40px;
  display: block;
}
.promotion-title-2line {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  line-clamp: 2;
  max-height: 2.8em;
  min-height: 2.6em;
  line-height: 1.3em;
}
.promotion-date-fixed {
  font-size: 0.8rem !important;
  background: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 18px;
  padding: 2px 16px;
  font-weight: 600;
  color: #111 !important;
  display: inline-block;
  z-index: 1;
  letter-spacing: 0.5px;
  margin-bottom: 0.1rem;
  margin-top: 0.1rem;
}
.promotion-date-row {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0.1rem 0 0.5rem 0;
  width: 100%;
  height: 10px;
  gap: 6px;
}
.promotion-date-row .line {
  width: 32px;
  height: 1px;
  background: #e0e0e0;
  margin: 0;
}
.promotion-excerpt-fixed {
  font-size: 0.8rem !important;
  min-height: 28px;
  text-align: center;
  margin-top: 0.3rem;
  color: #888;
  line-height: 1.5;
  word-break: break-word;
  white-space: normal;
  max-height: 38px;
  overflow: hidden;
}

@media (max-width: 991px) {
  .latest-articles-box { margin-bottom: 1.2rem; }
  .promotion-card { width: 100%; min-width: unset; }
  .promotion-img-fixed { max-width: 100%; }
}
