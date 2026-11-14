package com.example.aplikasi_inventaris_dmk

import android.graphics.Color
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import androidx.cardview.widget.CardView
import androidx.recyclerview.widget.RecyclerView
import com.example.aplikasi_inventaris_dmk.network.AssetItem

class InventoryAdapter(
    private var items: List<AssetItem>,
    private val onItemClick: (AssetItem) -> Unit
) : RecyclerView.Adapter<InventoryAdapter.ViewHolder>() {

    class ViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        val cardItemRoot: CardView  = itemView.findViewById(R.id.cardItemRoot)
        val tvCategoryTag: TextView = itemView.findViewById(R.id.tvCategoryTag)
        val tvUnitBadge: TextView   = itemView.findViewById(R.id.tvUnitBadge)
        val ivItemIcon: ImageView   = itemView.findViewById(R.id.ivItemIcon)
        val tvItemName: TextView    = itemView.findViewById(R.id.tvItemName)
        val tvItemSubtitle: TextView= itemView.findViewById(R.id.tvItemSubtitle)
        val tvItemLocation: TextView= itemView.findViewById(R.id.tvItemLocation)
        val tvItemStatus: TextView  = itemView.findViewById(R.id.tvItemStatus)
        val tvItemUnit: TextView    = itemView.findViewById(R.id.tvItemUnit)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_inventory_card, parent, false)
        return ViewHolder(view)
    }

    override fun getItemCount() = items.size

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val item = items[position]

        // Nama Aset
        val name = item.namaAset ?: "Aset #${item.kodeAset ?: "-"}"
        holder.tvItemName.text = name

        // Kategori
        val kategori = item.kategori ?: "Umum"
        holder.tvCategoryTag.text = kategori

        // Jumlah Unit (Menampilkan angka dari database)
        val totalUnit = item.unit ?: 1
        holder.tvUnitBadge.text = "$totalUnit Unit"
        holder.tvItemUnit.text  = "$totalUnit Unit"

        // Subtitle (Kode Aset & Merk dari database)
        val kode = item.kodeAset ?: "-"
        val subtitle = StringBuilder("Kode: $kode")
        var displayMerk = item.merk?.trim()
        val rawSpecs = item.spesifikasi?.trim() ?: ""
        if ((displayMerk.isNullOrBlank() || displayMerk == "-") && rawSpecs.contains("| Seri:")) {
            val ext = rawSpecs.substringBefore("|").trim()
            if (ext.isNotBlank() && ext != "-") displayMerk = ext
        }
        if (!displayMerk.isNullOrBlank() && displayMerk != "-") {
            subtitle.append(" • ").append(displayMerk)
        }
        holder.tvItemSubtitle.text = subtitle.toString()

        // Lokasi Aset
        holder.tvItemLocation.text = item.lokasi ?: "-"

        // Status Aset & Pewarnaan Badge (Baik, Rusak Ringan, Rusak Sedang, Rusak Berat)
        val statusRaw = (item.statusRaw ?: item.status ?: "Baik").trim()
        val statusUpper = statusRaw.uppercase()
        val statusShort = when {
            statusUpper.contains("RUSAK BERAT") || statusUpper.contains("RUSAK PARAH") -> "RUSAK BERAT"
            statusUpper.contains("RUSAK SEDANG") -> "RUSAK SEDANG"
            statusUpper.contains("RUSAK RINGAN") -> "RUSAK RINGAN"
            statusUpper.contains("RUSAK") || statusUpper.contains("PERBAIKAN") -> "RUSAK"
            statusUpper.contains("DIPINJAM") || statusUpper.contains("PINJAM") -> "DIPINJAM"
            statusUpper.contains("HILANG") -> "HILANG"
            statusUpper.contains("BAIK") -> "BAIK"
            statusUpper.contains("TERSEDIA") -> "TERSEDIA"
            else -> statusRaw.take(12)
        }
        holder.tvItemStatus.text = statusShort

        when {
            statusUpper.contains("RUSAK BERAT") || statusUpper.contains("HILANG") || statusUpper.contains("RUSAK PARAH") -> {
                holder.tvItemStatus.setBackgroundResource(R.drawable.bg_badge_red)
                holder.tvItemStatus.setTextColor(Color.parseColor("#C62828"))
            }
            statusUpper.contains("RUSAK") || statusUpper.contains("PERBAIKAN") || statusUpper.contains("DIPINJAM") -> {
                holder.tvItemStatus.setBackgroundResource(R.drawable.bg_badge_orange)
                holder.tvItemStatus.setTextColor(Color.parseColor("#E65100"))
            }
            else -> {
                holder.tvItemStatus.setBackgroundResource(R.drawable.bg_badge_green)
                holder.tvItemStatus.setTextColor(Color.parseColor("#2E7D32"))
            }
        }

        // Ikon berdasarkan kategori / nama barang
        val checkStr = (name + " " + kategori).lowercase()
        when {
            checkStr.contains("kendaraan") || checkStr.contains("mobil") || checkStr.contains("motor") ->
                holder.ivItemIcon.setImageResource(android.R.drawable.ic_menu_directions)
            checkStr.contains("elektronik") || checkStr.contains("laptop") || checkStr.contains("komputer") ||
                    checkStr.contains("printer") || checkStr.contains("timbangan") ->
                holder.ivItemIcon.setImageResource(android.R.drawable.ic_menu_slideshow)
            checkStr.contains("kamera") || checkStr.contains("proyektor") || checkStr.contains("cctv") ->
                holder.ivItemIcon.setImageResource(android.R.drawable.ic_menu_camera)
            checkStr.contains("mouse") || checkStr.contains("keyboard") || checkStr.contains("aksesoris") ->
                holder.ivItemIcon.setImageResource(android.R.drawable.ic_menu_recent_history)
            checkStr.contains("furniture") || checkStr.contains("meja") || checkStr.contains("kursi") ->
                holder.ivItemIcon.setImageResource(android.R.drawable.ic_menu_edit)
            else ->
                holder.ivItemIcon.setImageResource(android.R.drawable.ic_menu_agenda)
        }

        // Aksi klik card item
        holder.cardItemRoot.setOnClickListener { onItemClick(item) }
    }

    fun updateData(newItems: List<AssetItem>) {
        items = newItems
        notifyDataSetChanged()
    }
}