package com.example.aplikasi_inventaris_dmk

import android.graphics.Color
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import androidx.cardview.widget.CardView
import androidx.recyclerview.widget.RecyclerView
import com.example.aplikasi_inventaris_dmk.network.StokItem
import java.text.NumberFormat
import java.util.Locale

class DataStokAdapter(
    private var itemList: List<StokItem> = emptyList(),
    private val onItemClick: (StokItem) -> Unit
) : RecyclerView.Adapter<DataStokAdapter.StokViewHolder>() {

    private val numberFormat = NumberFormat.getNumberInstance(Locale.Builder().setLanguage("id").setRegion("ID").build())

    fun updateData(newList: List<StokItem>) {
        itemList = newList
        notifyDataSetChanged()
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): StokViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_data_stok, parent, false)
        return StokViewHolder(view)
    }

    override fun onBindViewHolder(holder: StokViewHolder, position: Int) {
        val item = itemList[position]
        holder.bind(item, position + 1)
    }

    override fun getItemCount(): Int = itemList.size

    inner class StokViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        private val cardRoot: CardView = itemView.findViewById(R.id.cardStokRoot)
        private val viewIndicator: View = itemView.findViewById(R.id.viewIndicatorStok)
        private val tvUrutan: TextView = itemView.findViewById(R.id.tvUrutanBadge)
        private val cardFotoStok: CardView = itemView.findViewById(R.id.cardFotoStok)
        private val ivFotoStok: ImageView = itemView.findViewById(R.id.ivFotoStok)
        private val tvNama: TextView = itemView.findViewById(R.id.tvNamaBarang)
        private val tvDetail: TextView = itemView.findViewById(R.id.tvDetailSatuan)
        private val tvJumlah: TextView = itemView.findViewById(R.id.tvJumlahStokBadge)
        private val tvStatus: TextView = itemView.findViewById(R.id.tvStatusTersedia)

        fun bind(item: StokItem, displayIndex: Int) {
            val context = itemView.context
            val urutanNo = item.urutan ?: displayIndex
            tvUrutan.text = String.format(Locale.US, "#%d", urutanNo)

            val nama = item.namaBarang?.trim().takeIf { !it.isNullOrBlank() } ?: "Barang Tanpa Nama"
            tvNama.text = nama

            val satuan = item.satuan?.trim().takeIf { !it.isNullOrBlank() && it != "-" } ?: "-"
            val ket = item.keterangan?.trim().takeIf { !it.isNullOrBlank() && it != "-" } ?: "-"

            val detailText = if (ket != "-") {
                "Satuan: $satuan • Ket: $ket"
            } else {
                "Satuan: $satuan"
            }
            tvDetail.text = detailText

            val jumlah = item.jumlah ?: 0
            val formattedJumlah = numberFormat.format(jumlah)
            val isTersedia = (item.tersedia == true) || (jumlah > 0)

            if (isTersedia) {
                viewIndicator.setBackgroundColor(Color.parseColor("#2E7D32"))
                tvJumlah.text = if (satuan != "-") "$formattedJumlah $satuan" else "$formattedJumlah Unit"
                tvJumlah.setBackgroundResource(R.drawable.bg_btn_blue_square)
                tvJumlah.setTextColor(Color.WHITE)

                tvStatus.text = "Tersedia"
                tvStatus.setTextColor(Color.parseColor("#2E7D32"))
            } else {
                viewIndicator.setBackgroundColor(Color.parseColor("#C62828"))
                tvJumlah.text = if (satuan != "-") "0 $satuan" else "0 Unit"
                tvJumlah.setBackgroundResource(R.drawable.bg_circle_red)
                tvJumlah.setTextColor(Color.WHITE)

                tvStatus.text = "Stok Habis"
                tvStatus.setTextColor(Color.parseColor("#C62828"))
            }

            // Reset state foto (mencegah glitch saat RecyclerView di-scroll)
            ivFotoStok.setImageDrawable(null)
            cardFotoStok.visibility = View.GONE
            tvUrutan.visibility = View.VISIBLE
            cardFotoStok.setOnClickListener(null)

            if (PhotoLoader.hasPhoto(item.fotoBarang, item.fotoUrl)) {
                val previewSubtitle = "Stok: $formattedJumlah ${if (satuan != "-") satuan else "Unit"} • #$urutanNo"
                val openPreview = View.OnClickListener {
                    PhotoLoader.showImagePreviewPopup(
                        context = context,
                        title = nama,
                        subtitle = previewSubtitle,
                        fotoBarang = item.fotoBarang,
                        fotoUrl = item.fotoUrl
                    )
                }
                PhotoLoader.loadInto(
                    context = context,
                    imageView = ivFotoStok,
                    fotoBarang = item.fotoBarang,
                    fotoUrl = item.fotoUrl,
                    maxDimPx = 256,
                    onLoaded = {
                        cardFotoStok.visibility = View.VISIBLE
                        tvUrutan.visibility = View.GONE
                        cardFotoStok.setOnClickListener(openPreview)
                    },
                    onFailed = {
                        cardFotoStok.visibility = View.GONE
                        tvUrutan.visibility = View.VISIBLE
                        cardFotoStok.setOnClickListener(null)
                    }
                )
            }

            cardRoot.setOnClickListener {
                onItemClick(item)
            }
        }
    }
}
